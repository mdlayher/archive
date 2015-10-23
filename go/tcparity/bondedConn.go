package main

import (
	"log"
	"net"
	"time"
)

// bondedConn represents a link between to net.Conn, in which reads/writes from one
// connection are automatically proxied to the other
// Thanks: http://www.stavros.io/posts/proxying-two-connections-go/
type bondedConn struct {
	connOne net.Conn
	connTwo net.Conn
}

// Proxy links two net.Conn together, and proxies connection read/writes between the two
func (b *bondedConn) Proxy(errChan chan error) {
	// Get channels for the connections
	oneChan := chanConn(b.connOne)
	twoChan := chanConn(b.connTwo)

	// Send bytes from one connection's channel into the other connection
	for {
		select {
		// First connection
		case res := <-oneChan:
			// Return on nil bytes
			if res == nil {
				return
			}

			// Send bytes
			_, err := b.connTwo.Write(res)
			if err != nil {
				errChan <- err
				break
			}
			break
		// Second connection
		case res := <-twoChan:
			// Return on nil bytes
			if res == nil {
				return
			}

			// Send bytes
			_, err := b.connOne.Write(res)
			if err != nil {
				errChan <- err
				break
			}
			break
		}
	}
}

// String creates a string representation of a bondedConn between two points
func (b *bondedConn) String() string {
	return b.connOne.RemoteAddr().String() + " <-> " + b.connTwo.RemoteAddr().String()
}

// chanConn creates a channel of bytes from a net.Conn, so that the bytes can be read using events
func chanConn(conn net.Conn) chan []byte {
	// Create channel
	connChan := make(chan []byte, 1)

	// Read events from channel
	go func() {
		buf := make([]byte, 4096)

		// Read data from the connection
		for {
			// Set deadlines for I/O to occur
			if err := conn.SetDeadline(time.Now().Add(5 * time.Second)); err != nil {
				log.Println(err)
				connChan <- nil
				break
			}

			// Read a buffer
			n, err := conn.Read(buf)
			if n > 0 && err == nil {
				// Copy buffer contents so they cannot be changed during reads
				res := make([]byte, n)
				copy(res, buf[:n])
				connChan <- res
			} else {
				// Else, return nil bytes
				connChan <- nil
				break
			}
		}

		// Close channel on nil byte send
		close(connChan)
		if err := conn.Close(); err != nil {
			log.Println(err)
		}

		return
	}()

	// Return channel for communication
	return connChan
}
