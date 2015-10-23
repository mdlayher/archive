/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The mash_execute() module is what handles piping, redirection, backgrounding,
		     forking, and execution of child processes.  It does the real grunt-work for the
		     shell, by setting up the necessary environment to do as the user pleases when
		     certain command-line tokens are specified.  The following tokens modify the behavior
		     of mash on command execution:
			1) '|': pipe; redirects stdout of one process to stdin of another
				(e.g. ls | grep matt | tail -n 1)
			2) '>': output redirection; redirects stdout to a specified file
				(e.g. ls /etc > /home/matt/myfile)
			3) '>>': output redirection (append); appends stdout to the specified file
				(e.g. ls /var/lib >> /home/matt/myfile)
			4) '<': input redirection; redirects a specified file to stdin
				(e.g. sort < /home/matt/myfile)
			5) '&': backgrounding; allows shell to continue doing other work while a process runs
				(e.g. sleep 5 &)
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - MASH EXECUTE - - - - - - - - - - - - - - - -

// mash_execute() does all I/O redirection, piping, and backgrounding for mash, as well as the execution
// of commands.  It is truly the heart and soul of the shell, and can pick off and execute any number
// of command pieces which are sent to it.
int mash_execute(int argc, char ** argp)
{
	// Secondary "argp", used to parse segments between pipes
	char ** exec_argp;

	// Command execute buffer
	char execute[MAXBUF] = { '\0' };

	// Store process ID
	pid_t pid;

	// Generic indexer variables
	int i,j,k = 0;

	// Set up I/O redirection file descriptor
	int ioRedir = 0;

	// Set up backgrounding flag
	int background = 0;

	// Set up piping hardware
	int pipeL[2];
	int pipeR[2];
	
	// Set up pipe counter
	int pipeCount = 0;
		
	// Create an array to hold pipe location indices
	int * pipeLoc;

	// Allocate memory for the pipe locations array
	if((pipeLoc = malloc(argc * sizeof(int))) == NULL)
	{
		printf("%s: memory allocation for pipe array failed!\n", SHELL_NAME);
		return -1;
	}
	
	// Detect pipes and backgrounding by iterating though arguments
	for(i = 0; i < argc; i++)
	{
		// If a pipe is found...
		if((strcmp("|", argp[i])) == 0)
		{
			// Store index where pipe was located in array
			pipeLoc[pipeCount] = i;

			// Increment number of pipes discovered
			pipeCount++;

			// Replace the pipe with a NULL
			argp[i] = NULL;
		}
		else if((strcmp("&", argp[i])) == 0)
		{
			// Enable process backgrounding
			background = 1;

			// Replace ampersand with a 0
			argp[i] = 0;
		}
	}
	
        // Set starting index to execute command directly after last pipe character
        i = pipeLoc[pipeCount - 1] + 1;

	// If no pipes were found, ensure 'i' has a default value of 0.
	if(pipeCount == 0)
		i = 0;

	// Reset k to i and begin checking for output redirection
	k = i;

	// Loop through arguments until a null is found
	while(argp[k] != NULL)
	{
		// Compare current token to output redirection angle-brackets
		// Create file, or append to it if it already exists
		if((strcmp(">>", argp[k])) == 0)
		{
			// Replace angle brackets with 0.
			argp[k] = 0;

			// Open output file as specified on the command-line
			if((ioRedir = open(argp[k + 1], O_WRONLY | O_CREAT | O_APPEND, 00644)) == -1)
			{
				printf("%s: could not open file for output redirection\n", SHELL_NAME);
				return -1;
			}

			// Redirect stdout stream to the output file
			if((dup2(ioRedir, STDOUT_FILENO)) == -1)
			{
				printf("%s: could not redirect stdout to output file\n", SHELL_NAME);
				return -1;
			}
		}
		// Create file, or truncate if it already exists
		else if((strcmp(">", argp[k])) == 0)
		{
			// Replace angle bracket with a 0.
			argp[k] = 0;
		
			// Open output file as specified on the command-line
			if((ioRedir = open(argp[k + 1], O_WRONLY | O_CREAT | O_TRUNC, 00644)) == -1)
			{
				printf("%s: could not open file for output redirection\n", SHELL_NAME);
				return -1;
			}
			
			// Redirect stdout stream to the output file
			if((dup2(ioRedir, STDOUT_FILENO)) == -1)
			{
				printf("%s: could not redirect stdout to output file\n", SHELL_NAME);
				return -1;
			}
		}
		
		// Increment k indexer
		k++;
	}

        // Loop until no pipes remain.
        while(pipeCount > 0)
        {
		// Copy information in from current arg, and add a space
                strcpy(execute, argp[i]);
                strcat(execute, " ");

                // Set j index to i + 1; use this index to parse arguments
                j = i + 1;

                // Parse arguments into execute buffer
                while(argp[j] != NULL)
                {
                        strcat(execute, argp[j]);
                        strcat(execute, " ");
                        j++;
                }

                // Tack a final null byte onto the execute buffer.
                execute[strlen(execute) - 1] = (char)0;

                // makeargv on execute buffer. creating arguments for additional commands
                argc = makeargv(execute, " \n\0", &exec_argp);

		// Open pipe between parent and child
		if((pipe(pipeL)) == -1)
		{
			printf("%s: failed to create pipe", SHELL_NAME);
			return -1;
		}

                // Fork a child process!
                switch(pid = fork())
                {
                        // If an error occurs, break and return an error
                        case -1:
                                printf("%s: failed to fork child!\n", SHELL_NAME);
				break;
                        // Spawn a child process, set up piping
                        case 0:
                                // Set pipe-right variables equal to pipe-left variables
                                pipeR[0] = pipeL[0];
                                pipeR[1] = pipeL[1];
                                
                                // Close read-end on the right
                                if((close(pipeR[0])) == -1)
				{
					printf("%s: failed to close pipe!\n", SHELL_NAME);
					return -1;
				}
                                
                                // Set up writing to a file from stdout
                                if((pipeR[1] = dup2(pipeR[1], STDOUT_FILENO)) == -1)
				{
					printf("%s: failed to redirect stdout to pipe!\n", SHELL_NAME);
					return -1;
				}
                                break;
                        // Have the parent process wait for the child to set up piping, then finish
                        // piping setup and execute commands.
                        default:
                                // Wait for child process to complete unless backgrounding
				if(background == 0)
				{
	                                if((wait(0)) == -1)
					{
						printf("%s: wait error on child processes\n", SHELL_NAME);
						return -1;
					}
				}
					
				// Close write-end on the left
				if((close(pipeL[1])) == -1)
				{
					printf("%s: failed to close pipe!\n", SHELL_NAME);
					return -1;
				}
				
				// Set up reading from a file instead of stdin
				if((pipeL[0] = dup2(pipeL[0], STDIN_FILENO)) == -1)
				{
					printf("%s: failed to redirect pipe to stdin!\n", SHELL_NAME);
					return -1;
				}

				// Move indices to the next pipe location
                                i = pipeLoc[pipeCount - 1]; 

	                        // Execute commands as they come down the pipe
				if((execvp(argp[i+1], exec_argp)) == -1)
				{
					err_sys("%s: could not execute '%s'", SHELL_NAME, argp[i+1]);
					return -1;
				}
                                break;
                }
                // Print parent process information out, decrement pipe count, loop.
                pipeCount--;
        }
        
        // Set k to 0 to begin input redirection search.
        k = 0;

	// Loop through input string starting at 0, searching for input redirection.
	while(argp[k] != NULL)
	{
		// Compare current token to input redirection angle-bracket
                if((strcmp("<", argp[k])) == 0)
                {
                        // Replace angle bracket with 0.
                        argp[k] = 0;

                        // Open output file as specified on the command-line
                        if((ioRedir = open(argp[k + 1], O_RDONLY, 00644)) == -1)
                        {
                                printf("%s: could not open file for input redirection\n", SHELL_NAME);
                                return -1;
                        }

                        // Redirect stdin stream to input file
                        if((dup2(ioRedir, STDIN_FILENO)) == -1)
                        {
                                printf("%s: could not redirect stdin to input file\n", SHELL_NAME);
                                return -1;
                        }
                }
                
                // Increment k indexer
                k++;
	}

	// Fork a child process to execute the final command
	switch(pid = fork())
	{
		// If an error occurs, break and return an error
		case -1:
			err_sys("%s: failed to fork child!", SHELL_NAME);
			return -1;
			break;
		// Spawn a child process, and execute a program
	case 0:
			// If child is backgrounded, print a message with its name and PID
			if(background == 1)
				printf("%s: %s : %d\n", SHELL_NAME, argp[0], (int)getpid());

			// Execute last command in the array
			if((execvp(argp[0], argp)) == -1)
			{
				err_sys("%s: could not execute '%s'", SHELL_NAME, argp[0]);
				return -1;
			}
			exit(0);
			break;
		// Have the parent shell wait for the child to terminate, unless backgrounding
		default:
			if(background == 0)
			{
				if((wait(0)) == -1)
				{
					err_sys("%s: wait error on child processes", SHELL_NAME);
					return -1;
				}
			}
			break;
	}

	// Close I/O redirection file descriptor
	if((close(ioRedir)) == -1)
	{
		printf("%s: could not close I/O redirection file\n", SHELL_NAME);
		return -1;
	}

	// Return success
	return 0;
}
