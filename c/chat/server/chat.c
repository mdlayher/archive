/*
	Authors: Justin Hill & Matt Layher
	Date:    2/14/12
	Updated: 3/26/12
	Project: chatd
	Module:  chat.c

	Description:
	The backend functions and database manipulation of the chatd server.
*/

//-------------------------- C LIBRARIES --------------------------------

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <signal.h>
#include <sys/socket.h>
#include <pthread.h>
#include <sqlite3.h>

//--------------------------- CUSTOM LIBRARIES --------------------------

#include "chat.h"
#include "functions.h"
#include "config.h"

//--------------------------- CHAT --------------------------------------

void *chat(void *arg)
{
	// Create all buffers needed for the user thread
	char inc[512], out[512], name[50], query[128], channel[64];

	// Create integers to keep track of user file descriptor and status
	int user_fd, status;

	// Clear all buffers needed in user thread
	memset(&inc, 0, sizeof(inc));
	memset(&out, 0, sizeof(out));
	memset(&name, 0, sizeof(name));
	memset(&query, 0, sizeof(query));
	memset(&channel, 0, sizeof(channel));

	// Create an SQLite statement
	sqlite3_stmt *stmt;

	// Set the file descriptor for this user according to the argument passed in to the thread startup function
	user_fd = *(int *)arg;

	// Set welcome message into output buffer using sprintf, send it to the user
	sprintf(out, "%s: %s welcome!  Enter a nickname: ", SERVER_NAME, USER_MSG);
	send_msg(user_fd, out);

	// Receive and store the nickname from the user
	recv_msg(user_fd, (char *)&name);
	clean_string(name);

	// Print message to console stating new user connected
	fprintf(stdout, "%s: %s new user: %s [fd: %d]\n", SERVER_NAME, INFO_MSG, name, user_fd);
	
	// Insert user into SQLite database, placing them in the default channel
	sprintf(query, "INSERT INTO users VALUES('%s', %d, '%s')", name, user_fd, DEFAULT_CHANNEL);

	// Set user's channel to the default channel	
	strcpy(channel, DEFAULT_CHANNEL);

	// Prepare and evaluate SQLite query
	sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
	if((status = sqlite3_step(stmt)) != SQLITE_DONE)
	{
		// On query failure, print an error to console and user, and close connection
		// Print console error
		fprintf(stderr, "%s: %s sqlite: user insert failed\n", SERVER_NAME, ERROR_MSG);

		// Send client an error message
		sprintf(out, "%s: %s database error.  Connection closed.\n", SERVER_NAME, ERROR_MSG);
		send_msg(user_fd, out);

		// Close user's connection, exit thread
		close(user_fd);
		return (void *)-1;
	}
	sqlite3_finalize(stmt);

	// If query succeeded, continue.
	// Display welcome message for user
	sprintf(out, "\n%s: %s welcome, %s.  Quit by typing '/quit' or '/q'.\n", SERVER_NAME, USER_MSG, name);
	send_msg(user_fd, out);

	// Broadcast to all users that this user has been placed in the default channel
	sprintf(out, "%s: %s '%s' joined channel '%s'\n", SERVER_NAME, USER_MSG, name, DEFAULT_CHANNEL);
	broadcast_msg(db, (char *)&channel, (char *)&out);

	// Loop infinitely until the user sends the /quit command
	while (1) 
	{
		// Receive a message from the user
		recv_msg(user_fd, (char *)&inc);
		clean_string(inc);

		//----------------------- PROCESS COMMANDS -------------------------

		// Check to see if first character is a slash.  If yes, it's a command
		if((strncmp(inc, "/", 1)) == 0)
		{
			// Make a copy of the incoming string because strtok will destroy it
			char incCopy[512];
			strcpy(incCopy, inc);
			
			// pick off the command to compare against.
			char *tok;
			tok = strtok(inc, " ");
			
			// /quit and /q - Quit.  Break the loop and proceed to quit routines, closing this client's connection and exiting this thread.
			if((strcmp(inc, "/quit") == 0) || (strcmp(inc, "/q") == 0))
				break;
			// /join and /j - Join.  Change the user's channel using switch_channel.
			else if((strcmp(inc, "/join") == 0) || (strcmp(inc, "/j") == 0))
			{
				// Grab channel name as second string
				tok = strtok(NULL, " ");
			
				// Ensure channel name starts with a pound sign
				if((strncmp(tok, "#", 1) == 0))
				{	
					// Attempt to switch to the new channel
					if(switch_channel(db, name, tok) == 0)
					{				
						// On success, broadcast leave message to this channel
						sprintf(out, "%s: %s '%s' left channel '%s'\n", SERVER_NAME, USER_MSG, name, channel);
						broadcast_msg(db, (char *)&channel, (char *)&out);

						// Change to this user's new channel
						strcpy(channel, tok);

						// Broadcast join message to new channel
						sprintf(out, "%s: %s '%s' joined channel '%s'\n", SERVER_NAME, USER_MSG, name, channel);
						broadcast_msg(db, (char *)&channel, (char *)&out);
					}
					else
					{
						// On failure, print message to user
						sprintf(out, "%s: %s could not switch channel to '%s'\n", SERVER_NAME, ERROR_MSG, tok);
						send_msg(user_fd, out);
					}
				}
				else
				{
					// If an invalid channel name was provided, warn the user
					sprintf(out, "%s: %s channel '%s' is invalid (valid channels start with '#')\n", SERVER_NAME, ERROR_MSG, tok);
					send_msg(user_fd, out);
				}
			}
			// /msg and /m - Message.  Send a private message to one user.
			else if((strcmp(inc, "/msg") == 0) || (strcmp(inc, "/m") == 0))
			{
				// A pointer, which we can set to the location after the skip characters.
				char *lookHere;

				// Index for counting loop and something to keep track of the number of spaces we encounter.
				int skipAhead, numSpaces;
				skipAhead = 1;
				numSpaces = 0;
				
				// Find the number of characters up to the second space (this will be the beginning of the message.)
				while(numSpaces < 2)
				{
					if(incCopy[skipAhead] == ' ')
						numSpaces++;
					skipAhead++;
				}
				
				// Give lookHere the address of the spot where we should start reading the message to send.
				lookHere = incCopy + skipAhead;
				
				// Keep a name buffer to keep track of recipient
				char recipient[25];
				memset(&recipient, 0, sizeof(recipient));
				
				// Now grab the name...
				tok = strtok(NULL, " ");			
				strcpy(recipient, tok);
				
				// Format message and send it away to the user
				sprintf(out, "[%s (PM)] %s\n", name, lookHere);
				private_msg(db, (char *)&recipient, (char *)&out);
			}
			// /global and /g - Global.  Send a global message to all users in all channels.
			else if((strcmp(inc, "/global") == 0) || (strcmp(inc, "/g") == 0))
			{
				// A pointer, which we can set to the location after the skip characters
				char *lookHere;

				// Index for counting loop and something to keep track of spaces we encounter
				int skipAhead, numSpaces;
				skipAhead = 1;
				numSpaces = 0;

				// Find the number of characters up to the first space (beginning of message)
				while(numSpaces < 1)
				{
					if(incCopy[skipAhead] == ' ')
						numSpaces++;
					skipAhead++;
				}

				// Give lookHere the address of the spot where we should begin reading the message
				lookHere = incCopy + skipAhead;

				// Format message, and send it to all users in all channels
				sprintf(out, "[%s (GLOBAL)] %s\n", name, lookHere);
				global_msg(db, (char *)&out);

				// Log global message to server log
				fprintf(stdout, "%s: %s %s", SERVER_NAME, INFO_MSG, out);
			}
			// /users and /u - Users.  Print a list of all users in your current channel.
			else if((strcmp(inc, "/users") == 0) || (strcmp(inc, "/u") == 0))
			{
				// Keep track of total users in channel
				int users_total = 0;

				// Print message stating that user list is being printed
				sprintf(out, "%s: %s list of users in channel '%s':\n", SERVER_NAME, INFO_MSG, channel);
				send_msg(user_fd, out);

				// Query for a list of all users in the current channel
				memset(&query, 0, sizeof(query));
				sprintf(query, "SELECT nick FROM users WHERE channel='%s' ORDER BY nick ASC", channel);

				// Prepare and evalute SQLite query
				sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
				while((status = sqlite3_step(stmt)) != SQLITE_DONE)
				{
					// Check for errors, and on error, exit this thread with an error, print error to console and user
					if(status == SQLITE_ERROR)
					{
						sprintf(out, "%s: %s database error while listing users\n", SERVER_NAME, ERROR_MSG);
						send_msg(user_fd, out);

						fprintf(stderr, "%s: %s sqlite: failed to retrieve listing of users in channel '%s'\n", SERVER_NAME, ERROR_MSG, channel);
						return (void *)-1;
					}
					else
					{
						// Increment users counter
						users_total++;

						// Else on success, print a user to the channel
						sprintf(out, "%s: %s %2d: %s\n", SERVER_NAME, USER_MSG, users_total, sqlite3_column_text(stmt, 0));
						send_msg(user_fd, out);
					}
				}

				// Print closing message for user list
				sprintf(out, "%s: %s end of list, %d user(s) in channel '%s'\n", SERVER_NAME, INFO_MSG, users_total, channel);
				send_msg(user_fd, out);
			}
			// /list and /l - List.  Print a list of all active channels and the number of users in each.
			else if((strcmp(inc, "/list") == 0) || (strcmp(inc, "/l") == 0))
			{
				// Print message stating that command doesn't work yet
				sprintf(out, "%s: %s commands /list and /l are not implemented yet\n", SERVER_NAME, INFO_MSG);
				send_msg(user_fd, out);

				/*
				// Keep tally of users in each channel, total users in all channels, and total number of channels
				int users_channel = 0;
				int users_total = 0;
				int channels_total = 0;

				// Create a second statement for the query to get number of users in a channel
				sqlite3_stmt *stmt2;

				// Print message stating that channel list is being printed
				sprintf(out, "%s: %s list of active channels:\n", SERVER_NAME, INFO_MSG);
				send_msg(user_fd, out);

				// Query for a list of all unique channels
				memset(&query, 0, sizeof(query));
				sprintf(query, "SELECT DISTINCT channel FROM users\n");

				// Prepare and evaluate SQLite query
				sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
				while((status = sqlite3_step(stmt)) != SQLITE_DONE)
				{
					// Check for errors, and on error, print a message to user, console, and exit thread
					if(status == SQLITE_ERROR)
					{
						sprintf(out, "%s: %s database error while listing channels\n", SERVER_NAME, ERROR_MSG);
						send_msg(user_fd, out);

						fprintf(stderr, "%s: %s sqlite: failed to retrieve listing of channels\n", SERVER_NAME, ERROR_MSG);
						pthread_exit((int *)-1);
					}
					else
					{
						// On success, increment channels counter
						channels_total;

						// Prepare inner query to retrieve number of users in the channel
						sprintf(query, "SELECT COUNT(filedescriptor) FROM users WHERE channel='%s'\n", sqlite3_column_text(stmt, 0));

						// Prepare and evaluate second SQLite query
						
					}

					// Increment channels counter
					channels_total++;

				}
				*/
			}
			// Else, command is not valid.  Print an error and restart loop.
			else
			{
				// Send user an error message
				sprintf(out, "%s: %s command '%s' is invalid\n", SERVER_NAME, USER_MSG, inc);
				send_msg(user_fd, out);

				// Log error to console
				fprintf(stderr, "%s: %s %s issued invalid command: '%s'\n", SERVER_NAME, ERROR_MSG, name, inc);
			}
		}
		else
		{
			// If input wasn't a command, print it as a message
			sprintf(out, "<%s> %s\n", name, inc);
			broadcast_msg(db, (char *)&channel, (char *)&out);
		}
	}

	// On client quit, broadcast a message to the channel, and print a server message, decrement client counter
	fprintf(stdout, "%s: %s %s [fd: %d] has disconnected [users: %d/%d]\n", SERVER_NAME, INFO_MSG, name, user_fd, client_count(-1), NUM_THREADS);
	sprintf(out, "%s: %s '%s' quit.\n", SERVER_NAME, USER_MSG, name);
	broadcast_msg(db, (char *)&channel, (char *)&out);

	// Remove user from database via query
	memset(&query, 0, sizeof(query));
	sprintf(query, "DELETE FROM users WHERE filedescriptor=%d OR nick='%s'", user_fd, name);
	
	// Prepare and evalute SQLite query
	sqlite3_prepare_v2(db, query, strlen(query) + 1, &stmt, NULL);
	if((sqlite3_step(stmt)) != SQLITE_DONE)
	{
		// On failure, print an error to the console.
		fprintf(stderr, "%s: %s sqlite: failed to purge user '%s' [fd: %d] from database\n", SERVER_NAME, ERROR_MSG, name, user_fd);
	}
	sqlite3_finalize(stmt);

	// Attempt to close socket
	if((close(user_fd)) == -1)
	{
		// On failure, print error to console, exit with bad return status
		fprintf(stderr, "%s: %s failed to close user socket", SERVER_NAME, ERROR_MSG);
		return (void *)-1;
	}

	// If all routines succeeded, return with success, so that thread may rejoin the pool
	return (void *)0;
}
