package main

// mockDataSource represents a mock data source which returns fake URLs
type mockDataSource struct {
}

// CheckRobots does nothing for a mock data source
func (d mockDataSource) CheckRobots() error {
	return nil
}

// Domain does nothing for a mock data source
func (d *mockDataSource) Domain(domain string) {
	return
}

// Get returns canned URLs from a mock data source
func (d mockDataSource) Get(url string) ([]string, error) {
	// Mock data URLs
	mockData := map[string][]string{
		// Valid cases
		"http://test.com/": []string{
			"http://test.com/a", "http://test.com/b",
		},
		"http://test.com/a": []string{
			"http://test.com/c",
		},
		"http://test.com/b": []string{
			"http://test.com/d",
		},
		// Case creates a cycle
		"http://test.com/c": []string{
			"http://test.com/a", "http://test.com/e",
		},
		// Case creates a cycle to self
		"http://test.com/d": []string{
			"http://test.com/d", "http://test.com/e", "http://test.com/f",
		},
		// Case contains relative URLs
		"http://test.com/e": []string{
			"/a", "/f", "/g",
		},
		// Case contains non-HTTP URLs
		"http://test.com/f": []string{
			"javascript:hello()", "mailto:test@test.com",
		},
	}

	// Check for return data
	data, ok := mockData[url]
	if !ok {
		// If no data, just return nil
		return nil, nil
	}

	// Process raw URLs to retrieve their contents
	return processConcurrent(data, "test.com", nil), nil
}
