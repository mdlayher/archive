package main

import (
	"log"
	"testing"
)

// TestProcessURL verifies that URLs are processed appropriately by the crawler
func TestProcessURL(t *testing.T) {
	log.Println("TestProcessURL()")

	// Create a table of raw, unprocessed URLs, and their processed equivalents
	var tests = []struct {
		source string
		target string
	}{
		// Regular URLs
		{"http://test.com/a", "http://test.com/a"},
		{"http://test.com/b", "http://test.com/b"},
		// Relative URLs
		{"a/", "http://test.com/a"},
		{"b", "http://test.com/b"},
		// Absolute URLs
		{"/a/", "http://test.com/a"},
		{"/b", "http://test.com/b"},
		// Empty URL
		{"", ""},
		{"href=\"", ""},
		{"src=\"", ""},
		// HTML prefixed
		{"href=\"http://test.com/a", "http://test.com/a"},
		{"src=\"http://test.com/img.jpg", "http://test.com/img.jpg"},
		// No HTTP prefix
		{"//test.com/script.js", "http://test.com/script.js"},
		// Trailing slashes
		{"http://test.com/a/b/", "http://test.com/a/b"},
		{"/a/", "http://test.com/a"},
		// Non-HTTP links
		{"javascript:hello()", ""},
		{"mailto:test@test.com", ""},
		// Blacklisted link
		{"/norobots", ""},
		// Cross-domain
		{"http://hello.com", ""},
		// Query string and page anchor
		{"http://test.com/a?hello=yes", "http://test.com/a"},
		{"/b?hello=no", "http://test.com/b"},
		{"http://test.com/c#anchor", "http://test.com/c"},
		{"/d#anchor2", "http://test.com/d"},
	}

	// Iterate test table, check results
	for _, test := range tests {
		// Process URL
		result := processURL(test.source, "test.com", []string{"/norobot"})

		// Verify match
		if result != test.target {
			t.Fatalf("TestProcessURL(): results do not match: %v != %v", result, test.target)
		}
	}
}
