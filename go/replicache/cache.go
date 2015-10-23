package main

import (
	"errors"
	"strconv"
	"sync"
)

// memcache is the cache which stores all data
var memcache = newCache()

// cache represents an in-memory key/value store
type cache struct {
	mutex sync.RWMutex
	cache map[string]string
}

// newCache initializes and returns a new cache struct
func newCache() *cache {
	return &cache{
		cache: map[string]string{},
	}
}

// Delete removes an item from cache using the specified key
func (c *cache) Delete(key string) error {
	// Delete item from cache
	c.mutex.Lock()
	delete(c.cache, key)
	c.mutex.Unlock()

	return nil
}

// Flush removes all items from the cache
func (c *cache) Flush() error {
	// Flush items from cache
	c.mutex.Lock()
	c.cache = map[string]string{}
	c.mutex.Unlock()

	return nil
}

// Get retrieves an item from cache using the specified key
func (c *cache) Get(key string) string {
	// Get item from cache
	c.mutex.Lock()
	v := c.cache[key]
	c.mutex.Unlock()

	return v
}

// Set stores an item into a cache using the specified key
func (c *cache) Set(key string, value interface{}) error {
	// Check type of value
	var strValue string
	switch value.(type) {
	case int:
		strValue = strconv.Itoa(value.(int))
	case string:
		strValue = value.(string)
	default:
		return errors.New("cache: unsupported value type")
	}

	// Store item in cache
	c.mutex.Lock()
	c.cache[key] = strValue
	c.mutex.Unlock()

	return nil
}
