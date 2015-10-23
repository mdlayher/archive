package main

import (
	"log"
	"net"
)

// listener accepts TCP connections and feeds them into the request channel
func listener(reqChan chan net.Conn, errChan chan error, haltChan chan bool) {
	// Start the TCP listener
	listen, err := net.Listen("tcp", ":8080")
	if err != nil {
		log.Println(err)
		errChan <- err
		return
	}

	// Close the listener upon halt
	go func() {
		// Wait for signal
		<-haltChan

		// Close the listener
		if err := listen.Close(); err != nil {
			log.Println(err)
			errChan <- err
			return
		}
	}()

	// Loop and accept connections
	for {
		// Get a connection from the listener
		conn, err := listen.Accept()
		if err != nil {
			log.Println(err)
			errChan <- err
			continue
		}

		// Send the connection to the request channel
		reqChan <- conn
	}
}
