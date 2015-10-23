#
#	Author:  Justin Hill & Matt Layher
#	Date:    3/21/12
#	Updated: 4/2/12
#	Project: chatc
#	Module:  Makefile
#
#	Description:
#	Makefile used to assemble the server program and all modules
#

CC=gcc
CFLAGS=-Wall -pedantic -std=gnu99 -g
LDFLAGS=-lpthread -lncurses
PROG=chatc

chatc:	main.o
		${CC} main.o -o ${PROG} ${LDFLAGS}
		rm *.o

main.o:	main.c
		${CC} ${CFLAGS} -c main.c -o main.o

clean:	
		-@rm -rf *.o logfile.txt chatc > /dev/null
