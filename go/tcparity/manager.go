package main

import (
	"log"
	"net"
	"os"
)

// server represents a server used for balancing with tcparity
type server struct {
	Host string
}

// manager is reponsible for coordinating the application and handling the main event loop
func manager(algoStr string, servers []string, killChan chan bool, exitChan chan int) {
	// Channels to handle incoming requests and outgoing responses
	reqChan := make(chan net.Conn, 1000)
	resChan := make(chan *bondedConn, 1000)

	// Current balancing algorithm, as set by flag
	var algorithm balanceAlgorithm
	switch algoStr {
	case "random":
		algorithm = new(randomAlgorithm)
	case "roundrobin":
		algorithm = new(roundRobinAlgorithm)
	default:
		log.Println(app, ": no such algorithm:", algoStr)
		os.Exit(1)
	}

	// Set servers from slice
	if err := algorithm.SetServers(servers); err != nil {
		log.Println(err)
		os.Exit(1)
		return
	}

	log.Println(app, ": algorithm:", algoStr)
	log.Println(app, ": servers:", servers)

	// Channel to change balancing algorithm used by tcparity
	algChan := make(chan *balanceAlgorithm, 0)

	// Channel to receive statistics from the application
	statChan := make(chan int, 1000)

	// Channel to handle errors from the application
	errChan := make(chan error, 1000)

	// Channel to trigger a graceful halt for various components
	haltChan := make(chan bool, 10)

	// Start the listener, to accept requests and feed them into the request channel
	go listener(reqChan, errChan, haltChan)

	// Loop and handle events
	for {
		select {
		// Stop the application
		case <-killChan:
			// Close all manager channels
			close(reqChan)
			close(resChan)
			close(statChan)
			close(errChan)
			close(haltChan)

			// Trigger graceful shutdown
			exitChan <- 0
			break
		// Handle incoming requests, send them to workers
		case request := <-reqChan:
			go worker(request, algorithm, resChan, errChan)
			break
		// Handle outgoing responses, proxy them and transfer data
		case response := <-resChan:
			if response != nil {
				log.Println(response)
			}

			go response.Proxy(errChan)
			break
		// Handle load balancing algorithm changes
		case algo := <-algChan:
			log.Println(app, ": setting algorithm:", algo)
			algorithm = *algo
			break
		// Handle application statistic processing
		case stat := <-statChan:
			go statworker(stat, errChan)
			break
		// Handle application errors
		case err := <-errChan:
			if err != nil {
				log.Println(err)
			}
			break
		}
	}
}
