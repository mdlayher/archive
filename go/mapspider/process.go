package main

import (
	"net/url"
	"strings"
)

// processConcurrent takes a slice of raw URLs, a domain, and a blacklist, and concurrently
// processes the URLs into a form appropriate for the crawler process to continue working
func processConcurrent(matches []string, domain string, blacklist []string) []string {
	// Channel to receive results of processing
	processChan := make(chan string, len(matches))

	// Process URLs concurrently
	for _, m := range matches {
		go func(url string) {
			// Process and resturn results
			processChan <- processURL(url, domain, blacklist)
		}(m)
	}

	// Wait for results
	received := 0
	urls := make([]string, 0)
	for p := range processChan {
		received++

		// Skip empty URLs, append
		if p != "" {
			urls = append(urls, p)
		}

		// Once all URLs received, break
		if received == len(matches) {
			break
		}
	}

	// Close channel
	close(processChan)

	return urls
}

// processURL takes a raw URL, a domain, and a blacklist, and processes the URL into
// a form appropriate for the crawler process to continue working
func processURL(m string, domain string, blacklist []string) string {
	// Check for "src" or "href", strip them
	if len(m) > 4 && m[0:4] == "src=" {
		m = m[5:len(m)]
	} else if len(m) > 5 && m[0:5] == "href=" {
		m = m[6:len(m)]
	}

	// Skip blank links
	if len(m) == 0 {
		return ""
	}

	// Skip JavaScript links
	if len(m) > 10 && m[0:10] == "javascript" {
		return ""
	}

	// Skip mailto links
	if len(m) > 6 && m[0:6] == "mailto" {
		return ""
	}

	// Check for non-prefixed links like "//"
	if len(m) > 2 && m[0:2] == "//" {
		m = "http:" + m
	}

	// Strip trailing slashes
	if m[len(m)-1] == uint8('/') {
		m = strings.TrimRight(m, "/")
	}

	// Strip query string
	if strings.Contains(m, "?") {
		m = m[0:strings.Index(m, "?")]
	}

	// Strip anchor
	if strings.Contains(m, "#") {
		m = m[0:strings.Index(m, "#")]
	}

	// Check if no slashes in URL (path with no domain)
	if !strings.Contains(m, "/") {
		m = "/" + m
	}

	// Parse URL to check its components
	link, err := url.Parse(m)
	if err != nil {
		return ""
	}

	// Check if path is blacklisted by robots.txt
	blacklisted := func(blacklist []string, link *url.URL) bool {
		for _, b := range blacklist {
			// If root '/' is blacklisted, always blacklisted
			if b == "/" {
				return true
			}

			// Make sure path is long enough to possibly match
			if len(link.Path) < len(b) {
				continue
			}

			// Check if item is in blacklist
			if link.Path[0:len(b)] == b {
				return true
			}
		}

		// Item not in blacklist
		return false
	}

	// Check blacklist
	if blacklisted(blacklist, link) {
		return ""
	}

	// If URL is not absolute, add the host
	if !link.IsAbs() {
		// Build the URL
		build := "http://" + domain
		if link.String()[0] == '/' {
			build = build + link.String()
		} else {
			build = build + "/" + link.String()
		}

		// Attempt to parse link again
		tempLink, err := url.Parse(build)
		if err != nil {
			return ""
		}

		link = tempLink
	}

	// Check if URL is not part of original domain
	if link.Host != domain {
		return ""
	}

	return link.String()
}
