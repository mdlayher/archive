package main

import (
	"net"
	"time"
)

// worker processes requests, and sends them to the response channel
func worker(request net.Conn, algorithm balanceAlgorithm, resChan chan *bondedConn, errChan chan error) {
	// Use the current algorithm to detect the appropriate server to dial
	server, err := algorithm.Balance()
	if err != nil {
		errChan <- err
		return
	}

	// Dial the selected server
	target, err := net.DialTimeout("tcp", server.Host, time.Duration(5*time.Second))
	if err != nil {
		errChan <- err

		// Close request on failure
		if err := request.Close(); err != nil {
			errChan <- err
		}
		return
	}

	// Bond the request connection and the target server connection
	resChan <- &bondedConn{request, target}
}
