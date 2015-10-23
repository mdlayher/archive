/*
	Authors: Justin Hill & Matt Layher
	Date:	 3/21/12
	Updated: 3/25/12
	Project: chatd
	Module:	 main.h

	Description:
	A header containing prototypes used in main.c
*/

//------------------------ PROTOTYPES ------------------------

// Daemonize function, used to detach a child from the parent and run the server in daemon mode
void daemonize();

// Print stats function, which prints out server statistics to console when called
void print_stats();

// TCP listener function, for the network thread
void *tcp_listen();
