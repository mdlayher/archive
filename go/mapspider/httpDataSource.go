package main

import (
	"io/ioutil"
	"log"
	"net/http"
	"regexp"
	"strings"
)

// httpDataSource represents a data source which fetches URLs via HTTP
type httpDataSource struct {
	blacklist []string
	domain    string
	userAgent string
}

// newHTTPDataSource creates a new HTTP data source
func newHTTPDataSource(userAgent string) dataSource {
	return &httpDataSource{
		blacklist: make([]string, 0),
		userAgent: userAgent,
	}
}

// CheckRobots checks the robots.txt file to find rules for web crawlers
func (d *httpDataSource) CheckRobots() error {
	robotsLink := "http://" + d.domain + "/robots.txt"
	log.Printf("%s: requesting robots.txt: %s", APP, robotsLink)

	// Perform HTTP GET request
	res, err := http.Get(robotsLink)
	if err != nil {
		return err
	}

	// Check for HTTP 404, meaning no robots.txt
	if res.StatusCode == 404 {
		log.Printf("%s: no robots.txt found", APP)
		return nil
	}

	// Read the entire response body
	bodyBuf, err := ioutil.ReadAll(res.Body)
	if err != nil {
		return err
	}

	// Clear, and re-initialize the blacklist
	d.blacklist = make([]string, 0)

	// Determine whether the crawler should obey robots.txt
	readDirectives := false

	// Iterate each line to find directives
	for _, line := range strings.Split(string(bodyBuf), "\n") {
		// Skip comments and empty lines, and stop reading directives
		if line == "" || line[0] == uint8('#') {
			// Print comments in robots.txt
			if len(line) > 0 && line[0] == uint8('#') {
				log.Println("robots.txt:", line)
			}

			continue
		}

		// Check for User-agent, describing which user agents this directive applies to
		if len(line) > 10 && line[0:11] == "User-Agent:" || len(line) > 10 && line[0:11] == "User-agent:" {
			// Check for this user agent, or wildcard
			if line[12] == uint8('*') || line[12:len(line)] == d.userAgent {
				// Follow directives, since they refer to this crawler
				readDirectives = true
			} else {
				// Do not follow directives, they do not apply to this crawler
				readDirectives = false
			}
		}

		// Check for Disallow, describing paths the crawler should not follow
		if len(line) > 8 && line[0:9] == "Disallow:" {
			// If no path provided, entire site can be crawled
			if len(line) < 10 {
				continue
			}

			// Add listed path to paths which are blacklisted
			if readDirectives {
				d.blacklist = append(d.blacklist, line[10:len(line)])
			}
		}

		// Check for Sitemap, describing a link to a map of this site
		if len(line) > 7 && line[0:8] == "Sitemap:" {
			log.Println("robots.txt: Sitemap:", line[9:len(line)])
		}
	}

	log.Printf("%s: blacklist: %v", APP, d.blacklist)

	// Close response body
	if err := res.Body.Close(); err != nil {
		return err
	}

	return nil
}

// Domain sets the domain where the crawler started operating
func (d *httpDataSource) Domain(domain string) {
	d.domain = domain
	return
}

// Get returns URLs which match a specific pattern, and are found from the current URL
func (d httpDataSource) Get(page string) ([]string, error) {
	// Perform HTTP GET request
	res, err := http.Get(page)
	if err != nil {
		return nil, err
	}

	// Close body on return
	defer res.Body.Close()

	// If response code is not HTTP 200 OK, skip this link
	if res.StatusCode != 200 {
		return nil, nil
	}

	// Check content type, only continue crawling HTML
	if contentType := res.Header.Get("Content-Type"); !strings.Contains(contentType, "text/html") {
		return nil, nil
	}

	// Read the entire response body
	bodyBuf, err := ioutil.ReadAll(res.Body)
	if err != nil {
		return nil, err
	}

	// Compile a regular expression to match URLs
	regex, err := regexp.Compile(`(href|src)=[\'"]?([^\'" >]+)`)
	if err != nil {
		return nil, err
	}

	// Match URLs from regular expression, process them
	return processConcurrent(regex.FindAllString(string(bodyBuf), -1), d.domain, d.blacklist), nil
}
