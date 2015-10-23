package main

import (
	"flag"
	"fmt"
	"os"
	"os/signal"
	"syscall"
	"time"
)

// app is the name of the application
const app = "replicache"

// version is the current application version
const version = "git-master"

// port is a flag which determines the port replicache runs on
var port = flag.Int("port", 3030, "The port which replicache binds itself to.")

// test is a flag which causes replicache to start, and exit shortly after
var test = flag.Bool("test", false, "Make replicache start, and exit shortly after. Used for testing.")

func main() {
	// Set up command line options
	flag.Parse()

	// If test mode, trigger quit shortly after startup
	// Used for CI tests, so that we ensure replicache starts up and is able to stop gracefully
	if *test {
		go func() {
			fmt.Println(app, ": launched in test mode")
			time.Sleep(5 * time.Second)

			fmt.Println(app, ": test mode triggering graceful shutdown")
			err := syscall.Kill(os.Getpid(), syscall.SIGTERM)
			if err != nil {
				fmt.Println(app, ": failed to invoke graceful shutdown, halting")
				os.Exit(1)
			}
		}()
	}

	// Launch manager via goroutine
	killChan := make(chan bool)
	exitChan := make(chan int)
	go manager(killChan, exitChan)

	// Gracefully handle termination via UNIX signal
	sigChan := make(chan os.Signal, 1)
	signal.Notify(sigChan, os.Interrupt)
	signal.Notify(sigChan, syscall.SIGTERM)
	for sig := range sigChan {
		// Trigger manager shutdown
		fmt.Println(app, ": caught signal:", sig)
		killChan <- true
		break
	}

	// Force terminate if signaled twice
	go func(sigChan chan os.Signal) {
		for sig := range sigChan {
			_ = sig
			fmt.Println(app, ": force halting now!")
			os.Exit(1)
		}
	}(sigChan)

	// Exit with specified code from manager
	code := <-exitChan
	fmt.Println(app, ": graceful shutdown complete")
	os.Exit(code)
}
