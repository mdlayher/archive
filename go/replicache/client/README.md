Usage
=====

Here is example usage of the replicache Go client library:

```go
package main

import (
	"log"

	// Import replicache client
	replicache "github.com/mdlayher/replicache/client"
)

func main() {
	// Connect to a local replicache server
	cache, err := replicache.New("localhost:3030")
	if err != nil {
		log.Fatalf(err.Error())
	}

	// Set a value into the cache
	if err := cache.Set("abc", 123); err != nil {
		log.Fatalf(err.Error())
	}

	// Retrieve the item from the cache
	v, err := cache.Get("abc")
	if err != nil {
		log.Fatalf(err.Error())
	}

	// abc: 123
	log.Println("abc:", v)

	// Delete the item from the cache
	if err := cache.Delete("abc"); err != nil {
		log.Fatalf(err.Error())
	}

	// Flush the entire cache of items
	if err := cache.Flush(); err != nil {
		log.Fatalf(err.Error())
	}

	// Close connection to the cache
	if err := cache.Close(); err != nil {
		log.Fatalf(err.Error())
	}
}
```
