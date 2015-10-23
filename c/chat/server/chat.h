/*
	Authors: Justin Hill & Matt Layher
	Date:	 3/25/12
	Updated: 3/25/12
	Project: chatd
	Module:  chat.h

	Description:
	A header containing prototypes and global variables used in chat.c
*/

//------------------------ GLOBAL VARIABLES ------------------

// Reference the externally defined SQLite database, which is opened in main
extern sqlite3 *db;

//------------------------ PROTOTYPES ------------------------

// Chat function prototype, which handles the heavy lifting for the chat server
void *chat(void *);
