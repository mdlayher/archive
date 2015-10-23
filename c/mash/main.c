/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The primary driver program for mash.  This is the master program which calls
		     and operates all other modules.  It loads the ~/.mashrc configuration file,
		     then receives input from the user in the form a character buffer.  The character
		     buffer is processed in the following ways:
			1) Check for bad input
			2) Alias replacement (e.g. replacing alias '..' with command 'cd ..')
			3) Character replacement (e.g. replacing character '~' with '/home/matt')
			4) History replacement (e.g. replacing '!' with last command executed)
			5) Check for built-in commands (mash_command)
			6) Set executed command into history buffer
			7) Fork an execution process (mash_execute)
		    The shell will loop infinitely until Ctrl+C is pressed or "exit" is provided
		    to its prompt.
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "modules/mash.h"

// - - - - - - - - - - - - - - MAIN - - - - - - - - - - - - - - - - - - - -

// The mothership of the shell which makes all the magic happen.  This method retrieves a command
// buffer, modifies it as necessary using aliases, character replacment, and history, and then
// executes it as a built-in routine or a process.  It will loop infinitely until provided the
// word "exit", or a Ctrl+C SIGINT is received.
int main()
{
	// The pointer to the array of pointers, used to tokenize a command buffer
	char ** argp;
	
	// Used to hold the number of arguments sent from makeargv
	int argc = 0;

	// Generic indexer variables
	int i = 0;

	// Buffer to store and execute commands
	char command[MAXBUF] = { '\0' };

	// Buffer to remember which command was executed last
	char lastCommand[MAXBUF] = { '\0' };

	// Declare an array of valid built-in shell commands
	char validFunctions[V_NUM][15] = { "alias", "back", "cd", "get", "help", "option", "rc-reload" };

	// Declare a variable which controls skipping of all stages due to bad input
	int badInput = 0;
	
	// Declare a variable which controls skip of forking stage
	int skipFork = 0;
	
	// PID to store PID of child shell, to stop parent shell from dying on errors
	pid_t pid;

	// Load in ~/.mashrc configuration file, which sets up shell options and aliases
	if((load_mashrc()) == -1)
	{
		err_sys("%s: could not load ~/.mashrc", SHELL_NAME);
		return -1;
	}

	// Prompt user for input, error-checking for failed system calls.
	if((prompt()) == -1)
	{
		err_sys("%s: system call failure", SHELL_NAME);
		return -1;
	}

	// User command; runs until user types exit.
	while(strcmp(fgets(command, MAXBUF, stdin), "exit\n") != 0)
	{	
		// Check for blank input to prevent shell crashes
		if((strcmp("\n", command)) == 0)
			badInput = 1;

		// If input is okay, begin parsing arguments and command execution
		if(badInput == 0)
		{
			// argc contains the number of tokens read in.  Parse tokens for alias checking.
			argc = makeargv(command, " \t\n", &argp);

			// Check if any part of the string is an alias
			alias_replace((char*)&command, argc, argp);

			// Check for character aliases (ex: ~ for /home/USER)
			char_replace((char*)&command, (char)'~', (char*)getenv("HOME"));
			char_replace((char*)&command, (char)'$', (char*)getenv("SHELL"));

			// If an exclamation point is detected, call the history and print out the new command
			if((char_replace((char*)&command, (char)'!', (char*)lastCommand)) == 1)
				printf("%s: %s", SHELL_NAME, command);

			// Rebuild argp to catch any changes which may have been made by previous routines
			argc = makeargv(command, " \t\n", &argp);	

			// Check if the command provided is a built-in shell command.
			for(i = 0; i < V_NUM; i++)
			{
				// If a command is matched, execute it and skip the forking stage.
				if((strcmp(validFunctions[i], argp[0])) == 0)
				{
					// Attempt to execute the built-in command, and error-check execution.
					if((mash_command(argc, argp)) == -1)
					{
						err_sys("%s: built-in command subroutines failed!\n", SHELL_NAME);
					}
					// Skip forking stage.
					skipFork = 1;
				}
			}

			// Flush history buffer, and copy command buffer to the history buffer
			memset(lastCommand, '\0', sizeof(lastCommand));
			strcpy(lastCommand, command);

			// Assuming the last command was not built-in, fork a child shell...
			if(skipFork == 0)
			{
				// Fork a child shell to do the execution and protect the parent shell from errors.
				switch(pid = fork())
				{
					// If an error occurs, break and return an error
					case -1:
						err_sys("%s: failed to fork child shell!", SHELL_NAME);
						return -1;
						break;
					// Spawn a child process, and execute a shell routine, protecting the parent shell
					// from any errors which may occur.
					case 0:
						if((mash_execute(argc, argp)) == -1)
						{
							err_sys("%s: command execution failed!\n", SHELL_NAME);
							return -1;
						}
						exit(0);
						break;
					// Have the parent shell wait for the child to terminate
					default:
						if((wait(0)) == -1)
						{
							err_sys("%s: wait error on child processes", SHELL_NAME);
							return -1;
						}
						break;
				}
			}
			// Reset variable which controls skipping fork stage.
			skipFork = 0;
		}		

		// Reset variable which controls skipping all stages if input is bad
		badInput = 0;

		// Prompt user for input, error-checking for failed system calls.
		if((prompt()) == -1)
		{
			err_sys("%s: system call failure", SHELL_NAME);
			return -1;
		}
	}
}
