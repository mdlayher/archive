/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The mashrc module contains the functionality required to parse and run mash's
		     configuration file, which is located in ~/.mashrc.  The configuration file
		     can contain comments, aliases, and options, which mash will parse and act
		     accordingly upon.  This allows the user to customize their mash environment,
		     and keep persistent settings state, even between runs of the shell.  If the
		     configuration file does not exist, mash will generate a basic one.
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - LOAD MASHRC - - - - - - - - - - - - - - - -

// load_mashrc() handles all of the dirty work of the creation and parsing of the ~/.mashrc configuration
// file.  This file can be used to customize mash for each user on a system, allowing them to have
// their own sets of aliases and options.
int load_mashrc()
{
	// Declare a pointer to an array of tokens
	char ** tokens;

	// Declare necessary variables and clean buffers 
	FILE * mashrc;
	char pathBuffer[MAXBUF] = { '\0' };
	char lineBuffer[MAXBUF] = { '\0' };

	// Declare a buffer to hold a string containing information and a default configuration for ~/.mashrc 
	char mashHeader[64] = "// .mashrc - configuration file for mash.\noption color_prompt 1\n";

	// argCount variable
	int argCount = 0;

	// Grab user's home directory and put it in pathBuffer
	strcpy(pathBuffer, getenv("HOME"));

	// Concatenate config file name to the path buffer
	strcat(pathBuffer, "/.mashrc");

	// Check to see if file exists.  If it does not, build it!
	if((file_exists(pathBuffer)) == -1)
	{
		// If it does not, open it for write and add a header line
		if((mashrc = fopen(pathBuffer, "w")) == NULL)
		{
			printf("%s: error creating ~/.mashrc\n", SHELL_NAME);
			return -1;
		}

		// Write a small header explaining how to use ~/.mashrc, and enable color_prompt.
		if((fwrite(mashHeader, sizeof(mashHeader), 1, mashrc)) == 0)
		{
			printf("%s: error writing headers to ~/.mashrc\n", SHELL_NAME);
			return -1;
		}

		// Close file in write mode
		if((fclose(mashrc)) != 0)
		{
			printf("%s: error closing ~/.mashrc\n", SHELL_NAME);
			return -1;
		}
	}

	// Open the mashrc file in read mode
	if((mashrc = fopen(pathBuffer, "r")) == NULL)
	{
		printf("%s: error opening ~/.mashrc\n", SHELL_NAME);
		return -1;
	}

	// Reset color_prompt to 0, so it can be changed using rc-reload
	color_prompt = 0;

	// Read the file until NULL
	while((fgets(lineBuffer, sizeof(lineBuffer), mashrc)) != NULL)
	{
		// Execute makeargv on the line, and store tokens in token array
		argCount = makeargv(lineBuffer, " \n\t", &tokens);

		// Retrieve aliases and store them in the global array
		if((strcmp("alias", tokens[0])) == 0)
		{
			// Call mash's aliasing subroutine (in silent mode) to do the work.
			if((mash_alias(argCount, tokens, 0)) == -1)
			{
				printf("%s: alias subroutine failure!\n", SHELL_NAME);
				return 0;
			}
		}
		// Retrieve options and use them to enable or disable global variables
		if((strcmp("option", tokens[0])) == 0)
		{
			// Call mash's option subroutine (in automatic mode) to do the work.
			mash_option(argCount, tokens, 0);
		}
	}

	// Close the input file
	if((fclose(mashrc)) != 0)
	{
		printf("%s: could not close ~/.mashrc\n", SHELL_NAME);
		return -1;
	}

	// Return success
	return 0;
}
