/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The mash_option() module provides functionality required for switching various
		     options on and off while running mash.  It can be called silently from the configuration
		     file, or verbosely from the command-line.  At this time, the only supported option
		     is 'color_prompt', which enables or disables the user's color prompt.
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - MASH OPTION - - - - - - - - - - - - - - - -

// mash_option() provides error-checking and option setting for mash, from either ~/.mashrc or from
// the command-line.  New options can be easily added by simply adding a global variable in mash.h
// and by adding a statement in here to toggle it.
int mash_option(int argc, char ** argp, int manual)
{
	// Check for correct syntax
	if(argc != 3)
	{
		// If the user is setting options via command-line...
		if(manual == 1)
			printf("%s: usage: option [name] [0/1]\n", SHELL_NAME);
		// Else, the options are coming from ~/.mashrc
		else
		{
			// Print out a slightly modified error message
			printf("%s: invalid syntax for option '%s' in ~/.mashrc\n", SHELL_NAME, argp[1]);
		}
		
		// Return 0, indicating that no errors occured
		return 0;
	}
	else
	{
		// Check for color_prompt option
		if((strcmp("color_prompt", argp[1])) == 0)
		{
			// Enable color prompt if needed
			if((strcmp("1", argp[2])) == 0)
				color_prompt = 1;
			else
				color_prompt = 0;

			// Print a message to signify the change if in manual mode
			if(manual == 1)
				printf("%s: option 'color_prompt' successfully set\n", SHELL_NAME);
		}
		// Print a message if no options were matched
		else
		{
			if(manual == 1)
				printf("%s: option '%s' does not exist\n", SHELL_NAME, argp[1]);
			else
				printf("%s: option '%s' from ~/.mashrc does not exist\n", SHELL_NAME, argp[1]);
			
		}
	}
	
	// Return 0 to indicate a successful run.
	return 0;
}
