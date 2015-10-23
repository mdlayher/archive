package main

import (
	"log"
	"os"
	"sync"
	"syscall"
	"time"

	replicache "github.com/mdlayher/replicache/client"
)

// manager is responsible for coordinating the application
func manager(killChan chan bool, exitChan chan int) {
	// Set up logging flags
	log.SetFlags(log.Ldate | log.Ltime | log.Lshortfile)
	log.Println("Starting " + app + " " + version)

	// Start TCP listener
	tcpSendChan := make(chan bool, 0)
	tcpRecvChan := make(chan bool, 0)
	go tcpListen(tcpSendChan, tcpRecvChan)

	// Main event loop
	for {
		select {
		// Shutdown the application
		case <-killChan:
			// Trigger a graceful shutdown
			log.Println("Triggering graceful shutdown, press Ctrl+C again to force halt")

			// If program hangs for more than 10 seconds, trigger a force halt
			go func() {
				<-time.After(10 * time.Second)
				log.Println("Timeout reached, triggering force halt")
				if err := syscall.Kill(os.Getpid(), syscall.SIGTERM); err != nil {
					log.Println(err.Error())
				}
			}()

			// Release all server connections in pool
			var wg sync.WaitGroup
			wg.Add(len(pool))
			for _, c := range pool {
				go func(c replicache.Client, wg *sync.WaitGroup) {
					if err := c.Close(); err != nil {
						log.Println(err.Error())

						wg.Done()
						return
					}

					log.Println("Closed connection:", c.Server())
					wg.Done()
				}(c, &wg)
			}

			// Wait for all connections to close
			wg.Wait()

			// Stop TCP listener
			tcpSendChan <- true
			if <-tcpRecvChan {
				log.Println("TCP listener stopped")
			}

			// Flush cache
			if err := memcache.Flush(); err != nil {
				log.Println(err.Error())
				exitChan <- 1
			}

			// Report that program should exit gracefully
			exitChan <- 0
		}
	}
}
