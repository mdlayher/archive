CFLAGS=-Wall -pedantic -std=c99
CPATH=modules/

mash:		main.o alias.o command.o error.o execute.o makeargv.o mashrc.o option.o prompt.o subroutines.o
		gcc main.o alias.o command.o error.o execute.o makeargv.o mashrc.o option.o prompt.o subroutines.o -o mash
		rm *.o

main.o:		main.c
		gcc ${CFLAGS} -c main.c -o main.o

alias.o:	${CPATH}alias.c
		gcc ${CFLAGS} -c ${CPATH}alias.c -o alias.o

command.o:	${CPATH}command.c
		gcc ${CFLAGS} -c ${CPATH}command.c -o command.o

error.o:	${CPATH}error.c
		gcc ${CFLAGS} -c ${CPATH}error.c -o error.o

execute.o:	${CPATH}execute.c
		gcc ${CFLAGS} -c ${CPATH}execute.c -o execute.o

makeargv.o:	${CPATH}makeargv.c
		gcc ${CFLAGS} -c ${CPATH}makeargv.c -o makeargv.o

mashrc.o:	${CPATH}mashrc.c
		gcc ${CFLAGS} -c ${CPATH}mashrc.c -o mashrc.o

option.o:	${CPATH}option.c
		gcc ${CFLAGS} -c ${CPATH}option.c -o option.o

prompt.o:	${CPATH}prompt.c
		gcc ${CFLAGS} -c ${CPATH}prompt.c -o prompt.o

subroutines.o:	${CPATH}subroutines.c
		gcc ${CFLAGS} -c ${CPATH}subroutines.c -o subroutines.o

install:
		sudo cp mash /usr/bin/

clean:
		rm *.o mash
