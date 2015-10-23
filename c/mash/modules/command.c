/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The mash_command() module contains all built-in functions for mash, including
		     'alias', 'back', 'cd', 'get', 'help', 'option', and 'rc-reload'.  Each of these
		     functions are built-ins which mash will handle itself, instead of forking a child
		     process to do the work.  These generally provide auxiliary shell function, such
		     as setting aliases and options, providing a help menu, and walking around the
		     filesystem.
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - MASH COMMAND - - - - - - - - - - - - - - - -

// mash_command() parses a set of input tokens for any built-in commands which mash can handle
// on its own.  These provide an array of functionality which many standard shells today
// implement in order to benefit the user.
int mash_command(int argc, char ** argp)
{
	// 'alias' - create aliases to use with mash
	if((strcmp("alias", argp[0])) == 0)
	{
		// Call mash's aliasing subroutine (in verbose mode) to do the work.
		if((mash_alias(argc, argp, 1)) == -1)
		{
			printf("%s: alias subroutine failure!\n", SHELL_NAME);
			return -1;
		}
		
		// Return 0 for success.
		return 0;
	}
	
	// 'back' - change to previous working directory
	if((strcmp("back", argp[0])) == 0)
	{
		// Attempt navigation to the directory specified in $OLDPWD
		if((chdir(getenv("OLDPWD"))) != 0)
		{
			// Return control to shell if an error occurs.
			printf("%s: could not navigate to previous working directory\n", SHELL_NAME);
			return -1;
		}
		
		// Return success.
		return 0;
	}

	// 'cd' - change working directory
	if((strcmp("cd", argp[0])) == 0)
	{
		// Buffer to store the current working directory
		char path[MAXBUF];
	
		// Get the current working directory
		if((getcwd(path, (size_t)sizeof(path))) == NULL)
		{
			err_sys("%s: could not retrieve current working directory", SHELL_NAME);
			return -1;
		}
	
		// Set $OLDPWD to current directory.
	        if((setenv("OLDPWD", path)) == -1)
		{
			printf("%s: cd: could not set OLDPWD environment variable\n", SHELL_NAME);
			return -1;
		}
	
		// If directory is not specified, navigate to the user's home directory
		if(argc == 1)
		{
			if((chdir(getenv("HOME"))) != 0)
			{
				// Return control to shell if an error occurs.
				printf("%s: could not navigate to home directory\n", SHELL_NAME);
				return 0;
			}
		}
		// Else, attempt to navigate to the directory specified.
		else
		{
			if((chdir(argp[1])) != 0)
			{
				// Return control to shell if directory could not be found.
				printf("%s: could not navigate to '%s'\n", SHELL_NAME, argp[1]);
				return 0;
			}
		}
		// Return success if no errors occured.
		return 0;
	}
	
	// 'get' - echo environment variable values
	if((strcmp("get", argp[0])) == 0)
	{
		// Check syntax, since this one is pretty simple
		if(argc != 2)
		{
			printf("%s: usage: get [VAR]\n", SHELL_NAME);
			return 0;
		}
		// If syntax is correct...
		else
		{
			// Attempt to grab information from an environment variable, and print it if one is found.
			if((getenv(argp[1])) == NULL)
				printf("%s: get: environment variable '%s' does not exist\n", SHELL_NAME, argp[1]);
			else
				printf("%s\n", getenv(argp[1]));
			return 0;
		}
	}

	// 'help' - prints out a help screen with all information about the shell
	if((strcmp("help", argp[0])) == 0)
	{
		printf("		                        ()    \n");
		printf("   ()()()  ()()      ()()()    ()()()  ()()() \n");
		printf("  ()    ()    ()  ()    ()  ()()      ()    ()\n");
		printf(" ()    ()    ()  ()    ()      ()()  ()    () \n");
		printf("()    ()    ()    ()()()  ()()()    ()    ()\n\n");
		
		printf("   Matt's Advanced SHell - v1.0 - 4/10/11   \n\n");
		
		printf("Internal commands:\n");
		printf("	alias [name] [command]	Create an alias to call a long command by a shorter name\n");
		printf("				    Specifying no parameters shows usage and current aliases\n");
		printf("	back			Change directory to previous directory\n");
		printf("	cd [path]		Change directory to specified directory\n");
		printf("	get [VAR]		Print the value of an environment variable to stdout\n");
		printf("	help			View this help dialog\n");
		printf("	option [name] [0/1]	Sets an option on or off on-the-fly\n");
		printf("	rc-reload		Reloads ~/.mashrc configuration file, re-parsing options and aliases\n\n");

		printf("Character aliases:\n");
		printf("	before: ~		Replaces tilde character in command with the user's home directory\n");
		printf("	after : /home/USER	    (e.g. /home/matt)\n\n");

		printf("	before: $		Replaces dollar sign in command with the user's main shell\n");
		printf("	after : /path/SHELL	    (e.g. /usr/bin/mash)\n\n");

		printf("	before: !		Replaces exclamation point with the command last executed\n");
		printf("	after : [history]	    (e.g. ls -l | grep helloworld)\n\n");

		// Return 0 for success.
		return 0;
	}

	// 'option' - sets shell options on the fly, during program execution
	if((strcmp("option", argp[0])) == 0)
	{	
		// Call mash_option() to handle options parsing (in manual mode)
		mash_option(argc, argp, 1);

		// Return success
		return 0;
	}

	// 'rc-reload' - reloads ~/.mashrc by flushing current settings, reading in the
	// configuration file, and re-parsing its settings
	if((strcmp("rc-reload", argp[0])) == 0)
	{
		// Free all memory allocated by the aliases array
		free(aliases);		

		// Set aliasCount to zero
		aliasCount = 0;

		// Re-parse ~/.mashrc using the load_mashrc() function
		if((load_mashrc()) == -1)
		{
			printf("%s: could not load ~/.mashrc\n", SHELL_NAME);
	                return -1;
		}
		else
		{
			// Print success message
			printf("%s: successfully reloaded ~/.mashrc\n", SHELL_NAME);
		}

		// Return success
		return 0;
	}
	
	// Return -1 if no built-in functions were detected.  (Shouldn't happen; just in case)
	return -1;
}
