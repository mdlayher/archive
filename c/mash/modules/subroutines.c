/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The subroutines module provides routines which are used to add small
		     functionality to mash, and simplify repetitive routines.  file_exists()
		     simply determines if a given file exists, and returns whether it does
		     or not.  char_replace() is used to replace a character in a string with
		     another given string.  For example, the character '~' can be replaced with
		     the user's home directory using this routine
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - FILE EXISTS - - - - - - - - - - - - - - - -

// Checks to determine if a given filename exists in the current filesystem.  Typically used in
// load_mashrc() to determine whether ~/.mashrc should be generated or opened in-place.
int file_exists(char * fileName)
{
        // Declare a stat struct to be used to test if the file exists
        struct stat s;

        // Stat the file name, putting results in sucess integer.
        if((stat(fileName, &s)) == -1)
        {
                printf("%s: %s does not exist, creating one now...\n", SHELL_NAME, fileName);
                return -1;
        }

        // Return 0 if file exists
        return 0;
}

// - - - - - - - - - - - - - - CHAR REPLACE - - - - - - - - - - - - - - - -

// Checks for a specified character in the command buffer, and replaces every instance of that
// character with a given string.  A good example of the use for this is replacing the '~' character
// with the user's home directory, as bash does.
int char_replace(char * command, char replace, char * replacement)
{
	// Declare two buffers for string manipulation, and nullify them.
	char startBuffer[MAXBUF] = { '\0' };
	char endBuffer[MAXBUF] = { '\0' };

	// Declare a generic indexer variable
	int i = 0;

	// Return variable, declared 1 if a replacement happened or 0 if one didn't.
	int ret = 0;

	// Iterate through the command buffer, seeking characters to replace.
	for(i = 0; i < strlen(command); i++)
	{
		// Replace specified character(s) with specified string
		if(command[i] == replace)
		{
			// Copy contents of buffer before the string we wish to replace
			strncpy(startBuffer, command, i);

			// Copy contents of buffer after the replace character
			strcpy(endBuffer, &command[i+1]);

			// Copy start buffer string into new command buffer
			strcpy(command, startBuffer);

			// Concatenate in the replacement string
			strcat(command, replacement);

			// Concatenate the final buffer onto the command
			strcat(command, endBuffer);

			ret = 1;
		}
	}

	// Return code to the caller (useful for history purposes)
	return ret;
}
