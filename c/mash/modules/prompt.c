/*
        Name: Matt Layher
        Date: 4/11/11
        Class: CS2240 - System Programming & Concepts
        Time: T/R @ 1:00pm
        Assignment: Homework 5 - shell

        Description: The prompt() module contains code required to parse information about the user's
		     environment, and then use that information to customize the user's shell prompt.
		     Color can be switched on or off using options.  A sample mash prompt has the
		     following form:
			-mash|matt-:/srv/dropbox/WMU/CS2240/shell
*/

// - - - - - - - - - - - - - - MASH HEADER - - - - - - - - - - - - - - - -
// The required header for all modules of mash, containing any globals and prototypes.
#include "mash.h"

// - - - - - - - - - - - - - - PROMPT - - - - - - - - - - - - - - - -

// Parses environment information and generates the user's shell prompt, as customized in this file
// and by the 'color_prompt' option from ~/.mashrc.  A modified prompt will be displayed when
// the user is logged in as root, to signify the danger of running commands as root.
int prompt()
{
	// Variables required to store information for mash's prompt
	char * user;
	struct passwd * pwd;
	char path[MAXBUF];

	// Get username of user who is executing the shell
	if((pwd = getpwuid(geteuid())) == NULL)
	{
		err_sys("%s: could not retrieve username", SHELL_NAME);
		return -1;
	}
	else
	{
		// Set user string to the name provdied by getpwuid()
		user = pwd->pw_name;
	}

	// Get the current working directory
	if((getcwd(path, (size_t)sizeof(path))) == NULL)
	{
		err_sys("%s: could not retrieve current working directory", SHELL_NAME);
		return -1;
	}
	
	// Display color prompt for user
	if(color_prompt == 1)
	{
		if((strcmp(user, "root")) == 0)
		{
			// Set up color root prompt
			printf("[\033[1;32m%s\033[m|\033[1;41m%s\033[m]:\033[1;34m%s\033[m \033[1;31m#\033[m ", SHELL_NAME, user, path);
		}
		else
		{
			// Set up color user prompt
			printf("-\033[1;32m%s\033[m|\033[1;36m%s\033[m-:\033[1;34m%s\033[m \033[1;36m$\033[m ", SHELL_NAME, user, path);
		}
	}
	// Display normal prompt for user
	else
	{
		if((strcmp(user, "root")) == 0)
		{
			// Set up black-and-white root prompt
			printf("[%s|%s]:%s # ", SHELL_NAME, user, path);
		}
		else
		{
			// Set up black-and-white user prompt	
			printf("-%s|%s-:%s $ ", SHELL_NAME, user, path);
		}
	}
	// Return 0 if all system calls were successful
	return 0;
}
