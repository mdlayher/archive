tcpd
====

tcpd is a simple base TCP server, utilized for several of my projects.  It makes use of Pithikos' C-Thread-Pool library (https://github.com/Pithikos/C-Thread-Pool) in order to allow a configurable number of clients to connect and utilize the server.  This code features a simple 'echo' service running on tcpd, which users can connect to via telnet in order to send a message to the server, and allow it to echo back a response.

tcpd is meant to be simple, clean, and quick.  By default, it provides a console interface which can directly control the server.  The server can be daemonized via the '-d' or '--daemon' flags, but can be stopped by issuing a SIGHUP, SIGINT, or SIGTERM signal.  Issuing SIGUSR1 or SIGUSR2 causes the server to print out some statistics while daemonized; the same can be accomplished by 'stat' on the console.
