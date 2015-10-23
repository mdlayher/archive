package main

import (
	"log"
	"runtime"
	"sync"
	"time"

	"github.com/mdlayher/godigraph"
	"github.com/mdlayher/goset"
)

// crawlerWork represents input/output work done by a worker
type crawlerWork struct {
	input  string
	output []string
}

// crawler begins the crawling process, and manages the workload
func crawler(root string, crawlerChan chan bool) {
	// Channel to queue up outgoing work
	workChan := make(chan string, 1000)

	// Channel to retrieve incoming results
	resultChan := make(chan *crawlerWork, 1000)

	// Set of visited locations
	visitSet := set.New()

	// Increase GOMAXPROCS, spawn workers according to CPU count
	runtime.GOMAXPROCS(runtime.NumCPU())
	numWorkers := runtime.NumCPU() * 4

	// Ensure worker count does not surpass a sane limit
	if numWorkers > 16 {
		numWorkers = 16
	}

	// WaitGroup to wait for all workers to be complete
	var wg sync.WaitGroup

	// Start a number of workers
	for i := 0; i < numWorkers; i++ {
		wg.Add(1)
		go worker(i, &wg, workChan, resultChan)
	}

	// Capture incoming results, add to graph, send out workers again
	go func() {
		// Wait for nil from all workers to stop gathering results
		nilCount := 0

		// Wait for results
		for r := range resultChan {
			// Check for nil, meaning worker completed
			if r == nil {
				nilCount++
			}

			// Check for all workers completed
			if nilCount == numWorkers {
				break
			}

			// On nil result, continue loop until all workers are done
			if r == nil {
				continue
			}

			// Add results to graph, dispatch workers to output locations
			for _, o := range r.output {
				// Add edges, but ignore the following errors from the digraph
				//  - ErrCycle: a cycle would be created, so edge not added
				//  - ErrEdgeExists: edge already exists, so edge not added
				if err := graph.AddEdge(r.input, o); err != nil && err != digraph.ErrCycle && err != digraph.ErrEdgeExists {
					log.Println(err)
					continue
				}

				// If location already visited, do not visit again
				if !visitSet.Has(o) {
					// Mark as visited now
					visitSet.Add(o)

					// Send new work to the queue
					workChan <- o
				}
			}
		}

		log.Printf("%s: work queue complete", APP)
	}()

	// Give a worker the root URL
	visitSet.Add(root)
	workChan <- root

	// Wait for all workers to complete
	wg.Wait()

	// Signal completion of crawler process
	crawlerChan <- true
}

// worker receives work from a channel, and returns results on another channel
func worker(id int, wg *sync.WaitGroup, workChan chan string, resultChan chan *crawlerWork) {
	log.Printf("worker[%02d]: starting", id)

	// Wait for work to be done
	workDoneChan := make(chan bool, 0)

	// Loop continuously until no work is left
	for {
		// Wait for work, or timeout
		select {
		// Timeout
		case <-time.After(3 * time.Second):
			// Stop worker
			log.Printf("worker[%02d]: timeout", id)
			resultChan <- nil
			wg.Done()
			return
		// Work
		case w := <-workChan:
			// Perform work
			log.Printf("worker[%02d]: %s", id, w)

			go func() {
				// Gather URLs from the data source
				urls, err := source.Get(w)
				if err != nil {
					log.Println(err.Error())
					workDoneChan <- true
					return
				}

				// Build and return results from crawl
				resultChan <- &crawlerWork{
					input:  w,
					output: urls,
				}

				workDoneChan <- true
			}()

			select {
			// Wait for work to be done
			case <-workDoneChan:
			// Time out long-running HTTP calls
			case <-time.After(time.Second * 3):
				log.Printf("worker[%02d]: GET timeout: %s", id, w)
			}

			// Check for more work
			if len(workChan) == 0 {
				// Stop worker
				log.Printf("worker[%02d]: done!", id)
				resultChan <- nil
				wg.Done()
				return
			}
		}
	}
}
