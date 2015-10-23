package main

import (
	"log"
	"net"
	"strconv"
	"strings"
	"sync"

	replicache "github.com/mdlayher/replicache/client"
)

// Pool of servers to replicate the cache
var pool = make([]replicache.Client, 0)

// tcpListen starts the TCP listening socket
func tcpListen(sendChan chan bool, recvChan chan bool) {
	// Resolve TCP address
	addr, err := net.ResolveTCPAddr("tcp", "localhost:"+strconv.Itoa(*port))
	if err != nil {
		log.Fatalf("Cannot resolve TCP address, exiting now.")
	}

	// Start TCP listener
	l, err := net.ListenTCP("tcp", addr)
	if err != nil {
		log.Fatalf("Cannot start TCP server, exiting now.")
	}
	log.Println("TCP listener started on port", *port)

	// TODO: use actual servers list, rather than these temporary entries
	servers := []string{"localhost:3030", "localhost:3031", "localhost:3032"}

	// Establish TCP listeners to other servers
	for _, s := range servers {
		// Resolve TCP address for servers
		sAddr, err := net.ResolveTCPAddr("tcp", s)
		if err != nil {
			log.Println("Could not resolve TCP address, skipping:", s)
			continue
		}

		// Check to ensure server does not link to itself
		if addr.IP.String() == sAddr.IP.String() && addr.Port == sAddr.Port {
			continue
		}

		// Establish connection
		c, err := replicache.New(s)
		if err != nil {
			// Skip unavailable servers
			log.Println("Could not establish link, skipping:", s)
			continue
		}

		// Link established, add to pool
		log.Println("Established link:", c.Server())
		pool = append(pool, *c)
	}

	// Set up TCP listener shutdown
	go func(l net.Listener, sendChan chan bool, recvChan chan bool) {
		// Wait for exit signal
		<-sendChan

		// Close listener
		if err := l.Close(); err != nil {
			log.Println(err.Error())
		}

		// Report exit
		recvChan <- true
	}(l, sendChan, recvChan)

	// Handle TCP connections
	for {
		conn, err := l.Accept()
		if err != nil {
			// Ignore connection closing error, caused by stopping network listener
			if !strings.Contains(err.Error(), "use of closed network connection") {
				log.Println(err.Error())
				continue
			}

			// Exit loop
			return
		}

		// Send TCP connections to handler
		go tcpHandler(conn)
	}
}

// tcpHandler handles incoming TCP connections
func tcpHandler(c net.Conn) {
	// Loop and handle connections
	for {
		// Read incoming data
		in := make([]byte, 512)
		n, err := c.Read(in)
		if err != nil && err.Error() != "EOF" {
			log.Println(err.Error())
			return
		}

		// Trim CR/LF
		in = []byte(strings.Trim(string(in[:n]), "\r\n"))

		// Parse command
		inArr := strings.Split(string(in), " ")
		action := inArr[0]

		// Check for quit
		quit := false

		// Output buffer
		out := make([]byte, 0)

		// Determine action
		switch action {
		// CLOSE - immediately close connection
		case "CLOSE":
			quit = true
			out = []byte("BYE\r\n")
		// DELETE - remove an item by key
		case "DELETE":
			// Verify enough arguments
			if len(inArr) != 2 {
				out = []byte("ERROR invalid syntax: DELETE [key]\r\n")
				break
			}

			// Delete item from cache
			if err := memcache.Delete(inArr[1]); err != nil {
				out = []byte("SERVER_ERROR " + err.Error())
				break
			}

			// Make all servers in pool delete item
			for _, c := range pool {
				go func(c replicache.Client) {
					if err := c.Delete(inArr[1]); err != nil {
						log.Println(err.Error())
					}
				}(c)
			}

			// delete OK
			out = []byte("OK\r\n")
		// FLUSH - flush all items from cache
		case "FLUSH":
			// Flush all items from cache
			if err := memcache.Flush(); err != nil {
				out = []byte("SERVER_ERROR " + err.Error() + "\r\n")
				break
			}

			// flush OK
			out = []byte("OK\r\n")
		// GET - retrieve an item by key
		case "GET":
			// Verify enough arguments
			if len(inArr) != 2 {
				out = []byte("ERROR invalid syntax: GET [key]\r\n")
				break
			}

			// Get item from cache
			value := memcache.Get(inArr[1])
			if value != "" {
				out = []byte("OK " + value + "\r\n")
			} else {
				out = []byte("OK\r\n")

				// Channel to wait for first response
				outChan := make(chan []byte, 1)

				// WaitGroup will trigger an empty response if no server has a response
				var wg sync.WaitGroup
				wg.Add(len(pool))

				// If value is empty, ask other servers in pool for item
				for _, c := range pool {
					go func(c replicache.Client, wg *sync.WaitGroup) {
						v, err := c.Get(inArr[1])

						// If no response or error, await next server
						if v == "" || err != nil {
							if err != nil {
								log.Println(err.Error())
							}

							// No response here
							wg.Done()
							return
						}

						log.Printf("GET %s -> %s, %s <- %s", inArr[1], c.Server(), v, c.Server())

						// Set into own cache
						go func() {
							if err := memcache.Set(inArr[1], v); err != nil {
								log.Println(err.Error())
							}
						}()

						// Return value to client
						outChan <- []byte("OK " + v + "\r\n")
						wg.Done()
					}(c, &wg)
				}

				// Check if all goroutines return, but still no response
				go func(wg *sync.WaitGroup) {
					// Trigger empty response if none found
					wg.Wait()
					outChan <- []byte("OK\r\n")
				}(&wg)

				// Return first response
				out = <-outChan
				close(outChan)
			}
		// SET - set an item using specified key
		case "SET":
			// Verify enough arguments
			if len(inArr) < 3 {
				out = []byte("ERROR invalid syntax: SET [key] [value]\r\n")
				break
			}

			// Retrieve all text starting at the index after the second space
			value := strings.SplitAfterN(string(in), " ", 3)[2]

			// Store item into cache
			if err := memcache.Set(inArr[1], value); err != nil {
				log.Println(err.Error())
				out = []byte("SERVER_ERROR " + err.Error() + "\r\n")
				break
			}

			// Store item into other servers in pool, if they do not already have it
			for _, c := range pool {
				go func(c replicache.Client) {
					// Check current value
					v, err := c.Get(inArr[1])
					if err != nil {
						log.Println(err.Error())
						return
					}

					// Check if value matches, skip set if it does
					if v == value {
						return
					}

					// Set item into other servers
					if err := c.Set(inArr[1], value); err != nil {
						log.Println(err.Error())
					}

					log.Printf("SET %s %s -> %s", inArr[1], value, c.Server())
				}(c)
			}

			// set OK
			out = []byte("OK\r\n")
		// Invalid command
		default:
			// Close connection on invalid command
			quit = true
			out = []byte("ERROR no such command\r\n")
		}

		// Write outgoing data
		if _, err := c.Write(out); err != nil && !strings.Contains(err.Error(), "broken pipe") {
			log.Println(err.Error())
			return
		}

		// Break loop on close
		if quit {
			break
		}
	}

	// Close connection
	if err := c.Close(); err != nil {
		log.Println(err.Error())
	}

	return
}
