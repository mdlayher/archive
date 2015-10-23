/*
	Authors: Justin Hill & Matt Layher
	Date:    3/20/12
	Updated: 3/25/12
	Project: chatd
	Module:  functions.c

	Description:
	A collection of helper functions and wrappers for various functions used by the server
*/

//------------------------ C LIBRARIES -----------------------

#include <ctype.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

//------------------------ CUSTOM LIBRARIES ------------------

#include "config.h"
#include "functions.h"

//------------------------ GLOBAL VARIABLES ------------------

// Client count, declared global so it may be manipulated via function and have only instance of itself
static int c_count = 0;

//------------------------ BROADCAST MSG ---------------------

// broadcast_msg() sends a message to all users in a specified channel (default chat behavior)
int broadcast_msg(sqlite3 *db, char *channel, char *message)
{
	// Keep track of number of bytes sent, and total bytes sent
	int b_sent = 0;
	int b_total = 0;

	// Create a query string, nullify it
	char query[128];
	memset(query, 0, sizeof(query));

	// Create SQLite statement and status variables
	sqlite3_stmt *stmt;
	int status;

	// Query for list of users in the given channel
	sprintf(query, "SELECT filedescriptor FROM users WHERE channel=\'%s\'", channel);

	// Prepare and evalute SQLite query
	sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
	while((status = sqlite3_step(stmt)) != SQLITE_DONE)
	{
		// Check for error.  If error occurs, print a console message and return failure
		if(status == SQLITE_ERROR)
		{
			fprintf(stderr, "%s: %s sqlite: failed to select list of users for broadcast\n", SERVER_NAME, ERROR_MSG);
			return -1;
		}
		else
		{
			// Else, on success, send message to the user currently focused in the loop.  Add bytes sent to total.
			b_sent = send_msg(sqlite3_column_int(stmt, 0), message);
			b_total += b_sent;
		}
	}
	sqlite3_finalize(stmt);

	// Once message has been sent to all users in channel, return number of bytes sent
	return b_total;
}

//------------------------ CLEAN STRING ----------------------

// clean_string() allows us to remove bad input characters from a string in memory
void clean_string(char *str)
{
	// Generic indexer variable
	int i = 0;

	// Create index to keep track of place in buffer
	int index = 0;

	// Keep buffer to copy in good characters
	char buffer[1024];

	// Iterate the string, removing any backspaces, newlines, and carriage returns
	for(i = 0; i < strlen(str); i++)
	{
		if(str[i] != '\b' && str[i] != '\n' && str[i] != '\r')
			buffer[index++] = str[i];
	}

	// Nullify the original input string
	memset(str, 0, sizeof(str));

	// Null terminate the buffer
	buffer[index] = '\0';

	// Copy the buffer back into the main string
	strcpy(str, buffer);
}

//------------------------ CLIENT COUNT ---------------------

// client_count() allows us to modify the client count for the server, and to simply return its value
//	1 - add one client
//	0 - return number of clients
//  -1 - remove one client
int client_count(int change)
{
	// Modify client counter by using change integer, return its value
	c_count += change;
	return c_count;
}

//------------------------ CONSOLE HELP ----------------------

// console_help() displays usage and information for console commands
void console_help()
{
	fprintf(stdout, "%s console commands:\n", SERVER_NAME);
	fprintf(stdout, "\tclear - clear the console\n");
	fprintf(stdout, "\t help - display available console commands\n");
	fprintf(stdout, "\t stat - display a quick server statistics summary\n");
	fprintf(stdout, "\t stop - terminate the server\n");
}

//------------------------ GET IN ADDR -----------------------

// get_in_addr() borrowed from Beej's Guide to Network Programming: http://beej.us/guide/bgnet/output/html/multipage/index.html
// This function allows us to easily create a string with the client's IP address
void *get_in_addr(struct sockaddr *sa)
{
        // In the case of IPv4, return the IPv4 address, else return the IPv6 address
        if (sa->sa_family == AF_INET)
                return &(((struct sockaddr_in*)sa)->sin_addr);
        else
                return &(((struct sockaddr_in6*)sa)->sin6_addr);
}

//------------------------ GLOBAL MSG -------------------------

// global_msg() sends a message to all users in every channel
int global_msg(sqlite3 *db, char *message)
{
	// Keep track of number of bytes sent, and total bytes sent
	int b_sent = 0;
	int b_total = 0;

	// Create a query string, nullify it
	char query[128];
	memset(query, 0, sizeof(query));

	// Create SQLite statement and status variables
	sqlite3_stmt *stmt;
	int status;

	// Query for list of users in all channels
	sprintf(query, "SELECT filedescriptor FROM users");

	// Prepare and evaluate SQLite query
	sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
	while((status = sqlite3_step(stmt)) != SQLITE_DONE)
	{
		// Check for error.  If error occurs, print a console message and return failure
		if(status == SQLITE_ERROR)
		{
			fprintf(stderr, "%s: %s sqlite: failed to select list of users for global broadcast\n", SERVER_NAME, ERROR_MSG);
			return -1;
		}
		else
		{
			// Else, on success, send message to the user currently focused in the loop.  Add bytes sent to total.
			b_sent = send_msg(sqlite3_column_int(stmt, 0), message);
			b_total += b_sent;
		}
	}
	sqlite3_finalize(stmt);

	// Once message has been sent to all users, return number of bytes sent
	return b_total;
}

//------------------------ PRIVATE MSG -----------------------

// private_msg() sends a private message to the specified user, so that other users may not see it
int private_msg(sqlite3 *db, char *user, char *message)
{
	// Create a query string, nullify it
	char query[128];
	memset(query, 0, sizeof(query));

	// Create SQLite statement
	sqlite3_stmt *stmt;

	// Query for file descriptor of user with the given username
	sprintf(query, "SELECT filedescriptor FROM users WHERE nick=\'%s\'", user);

	// Prepare and evaluate SQLite query
	sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
	if((sqlite3_step(stmt)) != SQLITE_ROW)
	{
		// An error occurred if status is not SQLITE_DONE, so print an error to console and return failure
		fprintf(stderr, "%s: %s sqlite: could not find file descriptor for user '%s'\n", SERVER_NAME, ERROR_MSG, user);
		return -1;
	}
	else
	{
		// Else, on success, send the private message to the user, returning total bytes sent.
		return send_msg(sqlite3_column_int(stmt, 0), message);
	}
	sqlite3_finalize(stmt);

	// Return zero for posterity, though this code should never be reached
	return 0;
}

//------------------------ RECV MSG --------------------------

// recv_msg() takes a file descriptor and a message, and abstracts the recv() sockets call
int recv_msg(int fd, char *message)
{
	// Keep track of number of bytes received, and total bytes received
	int b_received = 0;
	int b_total = 0;

	// Keep a buffer to gather input from recv() and copy it into the message
	char buffer[1024];

	// Nullify the input buffer before receiving data
	memset(buffer, '\0', sizeof(buffer));

	// Perform the recv() socket call, but handle the length and null termination for us, return the number of bytes
	b_received = recv(fd, buffer, sizeof(buffer), 0);
	b_total += b_received;

	// Copy buffer into message
	strcpy(message, buffer);

	// Return total bytes received
	return b_total;
}

//------------------------ SEND MSG --------------------------

// send_msg() takes a file descriptor and a message, and abstracts the send() sockets call
int send_msg(int fd, char *message)
{
	// Perform the send() socket call, but handle the length and null termination for us, return the number of bytes
	return send(fd, message, strlen(message), 0);
}

//------------------------- SWITCH CHANNEL -------------------

// switch_channel() changes a user's channel
int switch_channel(sqlite3 *db, char *name, char *destChannel)
{
	char query[96];
	sqlite3_stmt *stmt;
	int s;
	memset(&query, 0, sizeof(query));
	
	sprintf(query, "update users set channel = \'%s\' where nick = \'%s\'", destChannel, name);
	sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
	s = sqlite3_step(stmt);
	sqlite3_finalize(stmt);
	
	if(s == SQLITE_DONE) return 0;
	else return -1;
	
}

//------------------------- VALIDATE INT --------------------

// validate_int() ensures that an input string is a valid integer, and returns 1 on success, 0 on failure
int validate_int(char *string)
{
	// Flag to determine if integer is valid
	int isInt = 1;

	// Indexer variable
	int j = 0;

	// Loop through string, checking each digit to ensure it's an integer
        for(j = 0; j < strlen(string); j++)
        {
        	if(isInt == 1)
	        {
           	     if(!isdigit(string[j]))
                	     isInt = 0;
                }
        }

	// Return the value of isInt
	return isInt;
}
