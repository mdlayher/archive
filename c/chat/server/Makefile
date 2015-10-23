#
#	Author:  Justin Hill & Matt Layher
#	Date:    3/21/12
#	Updated: 3/25/12
#	Project: chatd
#	Module:  Makefile
#
#	Description:
#	Makefile used to assemble the server program and all modules
#

#---------- VARIABLES -----------------

# Define C compiler to be used
CC=gcc

# Define flags to be passed to the C compiler
CFLAGS=-Wall -pedantic -std=gnu99 -g

# Define flags for libraries to be linked when compiling the application
LDFLAGS=-lpthread -lsqlite3

# Define the name of the output program
PROG=chatd

# Define the name of the main configuration header
CFG=config.h

# Define the main of the main module
MAIN=main

# Define the name of the application module
APP=chat

# Define the name of the functions module
FUNC=functions

# Define the name of the threadpool module
TP=thpool

#---------- MAKEFILE -------------------

${PROG}:	${MAIN}.o ${APP}.o ${FUNC}.o ${TP}.o
		${CC} ${MAIN}.o ${APP}.o ${FUNC}.o ${TP}.o -o ${PROG} ${LDFLAGS}
		rm *.o

${MAIN}.o:	${MAIN}.c ${MAIN}.h ${CFG}
		${CC} ${CFLAGS} -c ${MAIN}.c -o ${MAIN}.o

${APP}.o:	${APP}.c ${APP}.h ${CFG}
		${CC} ${CFLAGS} -c ${APP}.c -o ${APP}.o

${FUNC}.o:	${FUNC}.c ${FUNC}.h ${CFG}
		${CC} ${CFLAGS} -c ${FUNC}.c -o ${FUNC}.o

${TP}.o:	${TP}.c ${TP}.h
		${CC} ${CFLAGS} -c ${TP}.c -o ${TP}.o

clean:
		rm ${PROG} *.o
