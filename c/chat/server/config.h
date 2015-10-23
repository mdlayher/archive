/*
	Authors: Justin Hill & Matt Layher
	Date:    3/20/12
	Updated: 3/25/12
	Project: chatd
	Module:  config.h

	Description:
	Global macros and configuration used to define many default settings and repetitive output for the server
*/

//-------------------- SERVER CONFIGURATION ----------------------

// Define the name of the server, as it will appear in the console window
#define SERVER_NAME "chatd"

// Define logging headers for various types of information in the console or log file
// OK, ERROR, and WARN messages utilize bash escape codes for color output
#define INFO_MSG  " info >>"
#define OK_MSG    "\033[1;32m   OK >>\033[0m"
#define ERROR_MSG "\033[1;31mERROR >>\033[0m"
#define WARN_MSG  "\033[1;33m WARN >>\033[0m"

// Define message separator sent to users for input
#define USER_MSG ">>"

//-------------------- SERVER DEFAULTS ---------------------------

// Define the SQLite database file which the program will use to store users
#define DB_FILE "chatdb.sqlite"

// Define the default port which the server will listen on, assuming another is not specified via argv array
#define DEFAULT_PORT "6560"

// Define the lockfile location for this server
#define LOCKFILE "/tmp/" SERVER_NAME ".lock"

// Define the number of threads created in our thread pool
#define NUM_THREADS 64

// Define the connection queue length for listening on the local socket
#define QUEUE_LENGTH 32

//-------------------- MISCELLANEOUS -----------------------------

// Define the default channel which new chat users are placed in
#define DEFAULT_CHANNEL "#default"

// Define the maximum valid TCP/UDP port
#define MAX_PORT 65535

// Define the maximum privileged TCP port
#define PRIVILEGED_PORT 1024

// Define the warning threshold for threadpool utilization
#define TP_UTIL 0.80
