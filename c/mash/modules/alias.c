/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The code in this module includes all necessary functions which are used to
		     generate and replace aliases in mash's command-line.  alias_replace() is a
		     function which modifies a command buffer by inserting tokens into it in place
		     of aliases, allowing for in-place alias use.  mash_alias() is the function which
		     generates and controls all aliases, by dynamically allocating memory for an
		     array of m_alias structs.  Providing the word "alias" with no arguments will
		     display all of these aliases, and providing three arguments will generate
		     a new alias.
			(e.g.) alias trollface cat /home/matt/trollface
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - ALIAS REPLACE - - - - - - - - - - - - - - -

// This function is used to replace any aliases from the command buffer with their designated
// commands from the array of alias structs.  Each time it finds an alias, it also rebuilds
// the command buffer so that more aliases can be found and replaced as needed.
int alias_replace(char * command, int argc, char ** argp)
{
	// Generic indexer variables
	int i,j,k = 0;

	// Iterate through command buffer, replacing aliases with commands
	for(i = 0; i < aliasCount; i++)
	{
		// Iterate through tokens in the command buffer
		for(j = 0; j < argc; j++)
		{
			// If a token matches an alias, replace the token with the alias command
			if((strcmp(aliases[i].ALIAS, argp[j])) == 0)
			{
				// Nullify command buffer
				memset(command, '\0', sizeof(command));

				// Replace token with alias command
				argp[j] = aliases[i].COMMAND;

				// Rebuild command buffer with replaced argp
				for(k = 0; k < argc; k++)
				{
					// Concatenate an argp to the command
					strcat(command, argp[k]);

					// Concatenate either a space or null terminator if this is
					// the last loop iteration
					if(k < argc - 1)

						strcat(command, " ");
					else
						command[strlen(command)] = (char)0;
				}
			}
		}
	}
	
	// Return success
	return 0;
}

// - - - - - - - - - - - - - - MASH ALIAS - - - - - - - - - - - - - - - -

// The mash_alias() function creates aliases and dynamically allocates memory for them as needed.
// It is capable of generating a near-infinite number of aliases (limited only by memory), as well
// as printing out all currently generated aliases by specifying no arguments to the "alias" command.
int mash_alias(int argc, char ** argp, int verbose)
{
	// Generic indexer variable
	int i = 0;

	// Allocate and clean a temporary buffer to store arguments
	char argBuffer[MAXBUF] = { '\0' };

	// Create a struct array which is used to expand the aliases array when necessary
	m_alias * expand;

	// Check for proper syntax
	if(argc < 3)
	{
		// If the user is generating aliases via command-line...
		if(verbose == 1)
		{
			// Print out the correct syntax and the current aliased commands
			printf("%s: usage: alias [name] [command]\n", SHELL_NAME);
			printf("\tcurrent aliases:\n");
			for(i = 0; i < aliasCount; i++)
				printf("\t[%2d] - %10s --> %s\n", i+1, aliases[i].ALIAS, aliases[i].COMMAND);
			printf("\n");
		}
		// Else, the aliases are coming from ~/.mashrc
		else
		{
			// Print out a less-verbose syntax error.
			printf("%s: invalid syntax for alias '%s' in ~/.mashrc\n", SHELL_NAME, argp[1]);
		}

		// Return success, indicating that no errors occured.
		return 0;
	}
	// Assuming three or more arguments are provided...
	else
	{
		// Check to see if an alias already exists under the same name
		for(i = 0; i < aliasCount; i++)
		{
			// If an alias already exists, print an error and quit.  Could possibly be used
			// to re-map aliases, but that could be messy.  It's easier this way.
			if((strcmp(aliases[i].ALIAS, argp[1])) == 0)
			{
				// Print out error messages if the alias exists, depending on if the alias
				// is called manually or from ~/.mashrc
				if(verbose == 1)
					printf("%s: alias '%s' (mapped to '%s') already exists!\n", SHELL_NAME, aliases[i].ALIAS, aliases[i].COMMAND);
				else
					printf("%s: alias '%s' already exists in ~/.mashrc\n", SHELL_NAME, aliases[i].ALIAS);
				
				// Return to caller
				return 0;
			}
		}
		
		// If no aliases already exist, allocate initial memory for an m_alias struct
		if(aliasCount == 0)
		{
			// Set aliasSize to size of one m_alias struct
			aliasSize = sizeof(m_alias);
	
			// Allocate initial memory for a new alias
			if((aliases = malloc(aliasSize)) == NULL)
			{
				printf("%s: failed to allocate initial memory for alias!\n", SHELL_NAME);
				return -1;
			}
		}
		else
		{
			// Increase aliasSize multipler by size of alias
			aliasSize = aliasSize + sizeof(m_alias);

			// Allocate memory for a new alias
			if((expand = realloc(aliases, aliasSize)) == NULL)
			{
				printf("%s: failed to allocate memory for alias!\n", SHELL_NAME);
				return -1;
			}

			// Copy the expanded array back into the primary array
			if(expand)
				aliases = expand;
			// Error-check
			else
			{
				printf("%s: could not reallocate memory to alias array!\n", SHELL_NAME);
				return -1;
			}
		}

		// If the alias does not already exist...
		// Copy alias name into the array's alias area
		strcpy(aliases[aliasCount].ALIAS, argp[1]);
		
		// Concatenate remaining arguments into command area
		for(i = 2; i < argc; i++)
		{
			// Concatenate arguments plus spaces
			if(i < argc - 1)
			{
				strcat(argBuffer, argp[i]);
				strcat(argBuffer, " ");
			}
			// Concatenate final argument, and null terminate
			else
			{
				strcat(argBuffer, argp[i]);
				argBuffer[strlen(argBuffer)] = (char)0;
			}
		}
		
		// Copy argBuffer into the array's command area
		strcpy(aliases[aliasCount].COMMAND, argBuffer);
		
		// Print success message if verbose is set.
		if(verbose == 1)
			printf("%s: alias '%s' successfully mapped to '%s'\n", SHELL_NAME, aliases[aliasCount].ALIAS, aliases[aliasCount].COMMAND);
		
		// Increment alias counter
		aliasCount++;

		// Return 0, indicating success.
		return 0;
	}
}
