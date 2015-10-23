/* from Robbins & Robbins: Unix Systems Programming p.37*/
#include <errno.h>
#include <stdlib.h>
#include <string.h>

int makeargv(const char *s, const char *delimiters, char ***argvp)
{
	// Declare necessary variables
	int error;
	int i;
	int numtokens;
	const char *snew;
	char *t;

	// If any parameters passed in are null, error out
	if ((s == NULL) || (delimiters == NULL) || (argvp == NULL))
	{
		errno = EINVAL;
		return -1;
	}

	// Nullify memory location ... ? @_@
	*argvp = NULL;

	// snew is the real start of the string
	snew = s + strspn(s, delimiters);

	// Allocate memory for the character array using the length of the new string, plus a null
	if ((t = malloc(strlen(snew) + 1)) == NULL) 
		return -1; 

	// Copy new string into some random string t.
	strcpy(t, snew);

	// Set number of tokens to 0.
	numtokens = 0;

	// Do crazy for loop.  If strtok does not return a null...
	if (strtok(t, delimiters) != NULL)     /* count the number of tokens in t */
		for (numtokens = 1; strtok(NULL, delimiters) != NULL; numtokens++);

	/* create argument array for ptrs to the tokens */
	if ((*argvp = malloc((numtokens + 1)*sizeof(char *))) == NULL)
	{
		// Error checking
		error = errno;
		free(t);
		errno = error;
		return -1;
	}

	/* insert pointers to tokens into the argument array */
	if (numtokens == 0)
		free(t);
	else
	{
		strcpy(t, snew);
		**argvp = strtok(t, delimiters);


		for (i = 1; i < numtokens; i++)
			*((*argvp) + i) = strtok(NULL, delimiters);
	} 

	*((*argvp) + numtokens) = NULL;             /* put in final NULL pointer */
	return numtokens;
}     

void freemakeargv(char **argv)
{
	if (argv == NULL)
		return;
	if (*argv != NULL)
		free(*argv);

	free(argv);
}
