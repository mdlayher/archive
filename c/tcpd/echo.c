/*
	Author:  Matt Layher
	Date:    3/20/12
	Project: tcpd
	Module:  echo.c

	Description:
	A small dummy application to demonstrate the TCP server.  Echo receives input,
	and echoes it back to the user.
*/

//------------------------ C LIBRARIES -----------------------

#include <stdio.h>
#include <string.h>
#include <unistd.h>

//------------------------ CUSTOM LIBRARIES ------------------

#include "config.h"
#include "functions.h"

//------------------------ APPLICATION NAME ------------------

#define APP_NAME "echo"

//------------------------ ECHO ------------------------------

// Simple dummy application which runs on the thread pool, receiving input and echoing it back
void *echo(void *arg)
{
	// Create and clear input and output buffers
	char in[512], out[512] = { '\0' };

	// Keep track of user's file descriptor as passed in by thread arguments
	int user_fd = *(int *)arg;

	// Send user initial welcome message
	sprintf(out, "%s: %s type a message, or /quit to quit\n", APP_NAME, USER_MSG);
	send_msg(user_fd, out);

	// Loop until the user sends in the quit command
	while (strncmp(in, "/quit", 5) != 0)
	{
		// Receive user's message, remove newlines
		recv_msg(user_fd, (char *)&in);
		if (in[strlen(in) - 1] == '\n' || in[strlen(in) - 1] == '\r')
		{
			in[strlen(in) - 1] = '\0';
		}

		// Echo it back to the user
		sprintf(out, "%s: %s received: %s\n", APP_NAME, USER_MSG, in);
		send_msg(user_fd, out);
	}

	// Send user goodbye message
	sprintf(out, "%s: %s goodbye!\n", APP_NAME, USER_MSG);
	send_msg(user_fd, out);

	// Clear I/O buffers
	memset(&in, 0, sizeof(in));
	memset(&out, 0, sizeof(out));

	// Decrement client count
	client_count(-1);

	// Print user disconnect message to console
	fprintf(stdout, "%s: %s client disconnected [fd: %d]\n", APP_NAME, OK_MSG, user_fd);

	// Attempt to close user socket
	if (close(user_fd) == -1)
	{
		// On failure, print error to console
		fprintf(stderr, "%s: %s failed to close user socket\n", APP_NAME, ERROR_MSG);
		return (void *)-1;
	}

	// If all routines succeded, return success, so that thread may rejoin the pool
	return (void *)0;
}
