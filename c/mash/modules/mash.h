/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The header file which contains all necessary prototypes and global
		     variables required for mash to function.  This allows for standardization
		     of many pieces of the shell, and for a very clean #include line at the
		     top of each mash module.
*/

// - - - - - - - - - - - - - - HEADERS - - - - - - - - - - - - - - - - - -
// The required set of headers for all modules of mash to function.
#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <fcntl.h>
#include <sys/wait.h>
#include <sys/stat.h>
#include <unistd.h>
#include <pwd.h>
#include "ourhdr.h"

// - - - - - - - - - - - - - - MACROS - - - - - - - - - - - - - - - - - -
// The name of the shell, in case we decide to change it later.
#define SHELL_NAME "mash"

// The standard buffer size for any module of mash
#define MAXBUF 1024

// The number of valid built-in functions which exist
#define V_NUM 8

// - - - - - - - - - - - - - - EXTERNAL PROTOTYPES - - - - - - - - - - -

// makeargv, the magic function that makes tokenizing buffers simple and easy.
extern int makeargv(char *, char *, char ***);

// Miscellaneous prototypes to keep gcc quiet and error-free
int setenv(char *, char *);
int gethostname(char *, size_t);

// - - - - - - - - - - - - - - INTERNAL PROTOTYPES - - - - - - - - - - -

// Module prototypes, which do the majority of the work for mash
int alias_replace(char *, int, char **);
int load_mashrc();
int mash_alias(int, char **, int);
int mash_command(int, char **);
int mash_execute(int, char **);
int mash_option(int, char **, int);
int prompt();

// mash subroutines, which perform simple, repetitive work for mash
int file_exists(char *);
int char_replace(char *, char, char *);

// - - - - - - - - - - - - - - ALIASES - - - - - - - - - - -

// Alias struct, which holds a command and an alias for that command.
typedef struct
{
        char ALIAS[MAXBUF];
        char COMMAND[MAXBUF];
} m_alias;

// Create an array of alias structs, whose size can be dynamically increased
m_alias * aliases;

// Keep track of the size of a single alias struct
int aliasSize;

// Keep track of number of aliases in the array
int aliasCount;

// - - - - - - - - - - - - - - OPTIONS - - - - - - - - - - - -

// Define whether or not the user wants a color prompt
int color_prompt;
