/*
	Author:  Justin Hill & Matt Layher
	Date:    3/21/12
	Updated: 4/17/12
	Project: chatc
	Module:  main.c

	Description:
	Contains everything having to do with the client.
*/

#include <ncurses.h>
#include <curses.h>
#include <stdio.h>
#include <signal.h>
#include <sys/ioctl.h>
#include <sys/types.h>
#include <unistd.h>
#include <string.h>
#include <netdb.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <stdlib.h>
#include <errno.h>
#include <pthread.h>

#define DEFAULT_PORT "6560"
#define MAXDATASIZE 1024

// window var for holding the box
WINDOW *textEntryBox;
WINDOW *chatHistoryBox;

// logfile
FILE *logfile;

// file descriptor of the socket connected to the chat server
int connection;

// hostname and port
char hostname[128];
char port[6];

// maximum number of bytes we're going to accept
int maxBytes = MAXDATASIZE;

// buffer for receiving message
char incomingMessage[1024];

// chat message pointer
char message[1024];

// prototype for sigwinch_callback_handler
void sigint_handler();
void sigwinchCallbackHandler(int signum);
void textEntryBox_Draw();
void *initializeTcpListener();

int main(int argc, char **argv)
{
	// Set default port
	strcpy(port, DEFAULT_PORT);

	// Ensure that all arguments are properly set
	if(argc <= 1 || argc >= 4)
	{
		fprintf(stderr, "usage: chatc server [port]\n");
		return 1;
	}

	// If a different port was set via command line, use it.
	if(argc == 3)
	{
		strcpy(port, argv[2]);
	}
	
	// logfile
	if((logfile = fopen("logfile.txt", "a")) == NULL)
	{
		fprintf(stderr, "Logfile failed to open\n");
	}
	
	fprintf(logfile, "Log file opened.\n");
	fflush(logfile);
	
	// pthread that we need for the network thing.
	pthread_t recv_thread;
	
	// set the hostname and port so we can access them later from the thread
	strcpy(hostname, argv[1]);
	fprintf(logfile, "Connecting to %s on port %s\n", hostname, port);
	
	// clear out the message buffer
	memset(&message, 0, sizeof(message));
	
	// init
	initscr();					// start curses mode
	cbreak();					// pass everything to me
	//noecho();					// don't echo keyboard input
	keypad(stdscr, TRUE);		// capture F-keys and arrows and stuff.
	
	// clear the screen
	refresh();
	
	// initialize tcp connection
	pthread_create(&recv_thread, NULL, &initializeTcpListener, NULL);

	// Install signal handler for Ctrl+C SIGINT
	signal(SIGINT, sigint_handler);
	
	// catch sigwinch
	signal(SIGWINCH, sigwinchCallbackHandler);
	
	// create a new window for text input
	textEntryBox = newwin(3, COLS, LINES - 3, 0);
	
	// create a new box for the chat history
	chatHistoryBox = newwin(LINES - 3, COLS, 0, 0);
	
	// make scrolling okay in the chatbox.
	idlok(chatHistoryBox, TRUE);
	scrollok(chatHistoryBox, TRUE);
	refresh();
	
	// take user input
	while(1)
	{
		// draw the box
		textEntryBox_Draw();
		
		// get the user's message
		mvwgetstr(textEntryBox, 1, 1, message);
		
		// check to see if we should quit now.
		if(strcmp(message, "/quit") == 0 || strcmp(message, "/q") == 0)
		{
			sigint_handler();
		}
		
		// print the message to the logfile (immediately)
		fprintf(logfile, "%s\n", message);
		fflush(logfile);
		send(connection, message, strlen(message), 0);
	}
	
	endwin();					// close curses mode
	return 0;
}

// this is called by a signal handler when the terminal changes size
void sigwinchCallbackHandler(int signum)
{	
	// get the new row/column sizes
	struct winsize ws;
	ioctl(0,TIOCGWINSZ,&ws);
	
	int windowWidth = ws.ws_col;
	int windowHeight = ws.ws_row;
	
	// resize stdscr to accomodate the new window size
	resizeterm(ws.ws_row, ws.ws_col);

	// clear the screen
	clear();
	wclear(textEntryBox);
	refresh();
	
	
	// make sure the text input window is still at the bottom of the screen
	if((mvwin(textEntryBox, windowHeight - 3, 0)) != OK)
	{
		fprintf(logfile, "Something borked when moving the window.");
	}
	
	wresize(textEntryBox, 3, windowWidth);
	textEntryBox_Draw();
	
	refresh();
}

void sigint_handler()
{
	// Generate quit command for server
	char quit[5];
	strcpy(quit, "/quit");

	// Send the quit command to the server, so it can handle this client's disconnect
	send(connection, quit, 5, 0);

	// Shutdown connection socket
	if((shutdown(connection, 2)) == -1)
	{
		fprintf(stderr, "ERROR: Failed to close socket\n");
		exit(-1);
	}

	// Close connection socket
	if((close(connection)) == -1)
	{
		fprintf(stderr, "ERROR: Failed to close socket\n");
	}

	// Exit client
	exit(0);
}

// draw the text entry box
void textEntryBox_Draw()
{
	wclear(textEntryBox);
	box(textEntryBox, 0, 0);
	mvwprintw(textEntryBox, 0, 1, "Type your message here: ");
	wrefresh(textEntryBox);
}

// Thread function?  Should be in another file. - matt

void *initializeTcpListener()
{
	// needed variables and structs
	struct addrinfo hints, *servinfo, *current;
	int ip;
	
	// initialize hints to zero
	memset(&hints, 0, sizeof(hints));
	
	// set some hints...
	hints.ai_family = AF_UNSPEC;
	hints.ai_socktype = SOCK_STREAM;
	
	// grab the address info
	if((ip = getaddrinfo(hostname, port, &hints, &servinfo)) != 0)
	{
		fprintf(stderr, "getaddrinfo: Error getting address info. Exiting.");
		pthread_exit((int *) -1);
	}
	
	// loop through the results and connect to the first address we can
	current = servinfo;
	while(current != NULL)
	{	
		// set up the socket.
		if((connection = socket(current->ai_family, current->ai_socktype, current->ai_protocol)) == -1)
		{
			fprintf(logfile, "Error setting up socket");
			current = current->ai_next;
			continue;
		}
		
		// try the connection
		if(connect(connection, current->ai_addr, current->ai_addrlen) == -1)
		{
			close(connection);
			fprintf(logfile, "Error making the connection.\n");
			current = current->ai_next;
			continue;
		}
		
		// if connect didn't return -1, we're connected!  hallelujah!
		else break;
	}
	
	// if we get through all that and current is equal to null, we were unable to find an address that
	// we could connect to.
	if(current == NULL)
	{
		fprintf(logfile, "Unable to connect to server.");
		pthread_exit((int *) -1);
	}
	
	// Don't need this anymore.
	freeaddrinfo(servinfo);
	
	while(1)
	{
		memset(&incomingMessage, 0, sizeof(incomingMessage));
		recv(connection, incomingMessage, sizeof(incomingMessage), 0);
		wprintw(chatHistoryBox, "%s", incomingMessage);
		wrefresh(chatHistoryBox);
		fprintf(logfile, "%s", incomingMessage);
		fflush(logfile);
	}
	
	// return great success
	pthread_exit((int *) -1);
}
