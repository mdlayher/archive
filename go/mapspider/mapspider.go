package main

import (
	"flag"
	"fmt"
	"log"
	"net/url"
	"os"
	"os/signal"
	"syscall"

	"github.com/mdlayher/godigraph"
)

// APP is the name of the application
const APP = "mapspider"

// graph is a digraph which stores crawled URLs
var graph *digraph.Digraph

// source is the data source from which URLs are fetched
var source dataSource

// allFlag determines if the entire graph should be printed, or if redundant vertices
// should not be printed
var allFlag = flag.Bool("all", false, "Print all graph edges and vertices, even redundant ones.")

// testFlag determines if we are fetching data from a mock source, or a real source
var testFlag = flag.Bool("test", false, "Fetch mock data, instead of data from a real source.")

// dataSource represents a data source from which URLs may be found
type dataSource interface {
	CheckRobots() error
	Domain(string)
	Get(string) ([]string, error)
}

func main() {
	flag.Parse()
	log.SetFlags(log.Ldate | log.Ltime | log.Lshortfile)

	// Check for a URL argument to begin crawling
	if len(os.Args) < 2 {
		log.Fatalf("usage: %s [-all] [-test] [url]", APP)
	}

	// Get the last argument as the URL
	root := os.Args[len(os.Args)-1]
	if root[0:4] != "http" {
		log.Fatalf("%s: URL missing 'http://' prefix: %s", APP, root)
	}

	// Attempt to parse URL for crawling
	url, err := url.Parse(root)
	if err != nil {
		log.Fatalf("%s: invalid URL: %s", APP, err.Error())
	}

	// Store URL root
	root = url.String()

	// Initialize the digraph for creating a URL sitemap, store starting vertex
	graph = digraph.New()
	if err := graph.AddVertex(root); err != nil {
		log.Fatalf("%s: failed to initialize digraph: %s", APP, err.Error())
	}

	// Initialize the data source, using mock if needed
	if *testFlag {
		source = new(mockDataSource)
	} else {
		source = newHTTPDataSource(APP)
	}

	// Set domain
	source.Domain(url.Host)

	// Check for robots.txt
	if err := source.CheckRobots(); err != nil {
		log.Fatalf("%s: could not check robots.txt", APP)
	}

	// Wait for completion of crawler process
	crawlerChan := make(chan bool, 0)

	// Launch crawler manager goroutine
	log.Printf("%s: crawling: %s", APP, root)
	go crawler(root, crawlerChan)

	// Check for incoming UNIX signals
	sigChan := make(chan os.Signal, 0)
	signal.Notify(sigChan, os.Interrupt)
	signal.Notify(sigChan, syscall.SIGTERM)

	// Wait for crawler completion, or an interrupt signal
	select {
	case <-crawlerChan:
		log.Printf("%s: crawl complete: %s", APP, root)
	case sig := <-sigChan:
		log.Printf("%s: caught signal: %s", APP, sig)
		os.Exit(1)
	}

	// Check number of vertices, and automatically disable -all option if the resulting graph
	// would be too large to effectively generate in memory
	printAll := *allFlag
	if printAll && graph.VertexCount() > 100 {
		log.Printf("%s: too many vertices (%d), disabling -all flag", APP, graph.VertexCount())
		printAll = false
	}

	// Fetch graph from the root vertex
	tree, err := graph.Print(root, printAll)
	if err != nil {
		log.Fatalf("%s: could not print result graph", APP)
	}

	// Print the final graph
	fmt.Println(tree)
	log.Printf("%s: vertices: %d, edges: %d", APP, graph.VertexCount(), graph.EdgeCount())
	log.Printf("%s: done!", APP)
}
