<?php

// The abstract load balancer from which all others derive
abstract class AbstractLoadBalancer
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - - -

	// Load balancing algorithm
	protected $algorithm;

	// Array of available servers in balancing pool
	protected $servers = array();

	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Set load balancing algorithm on instantiation
	public function __construct(IBalancingAlgorithm $algorithm)
	{
		$this->algorithm = $algorithm;
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - -

	// Add a server to load balancing pool
	public function add($protocol, $host, $port, $username = null, $password = null)
	{
		// Create a server, add server to pool
		$this->servers[] = new BalancedServer($protocol, $host, $port, $username, $password);
	}

	// Apply balancing algorithm and return a resource URI
	public function balance()
	{
		return (string)$this->algorithm->balance($this->servers);
	}

	// Apply balancing algorithm and return a resource stream
	public function proxy()
	{
		// Retrieve stream URI
		$uri = $this->balance();

		// Capture stream protocol
		$protocol = explode(":", $uri)[0];

		// Verify that stream is supported by PHP
		if (!in_array($protocol, stream_get_wrappers()))
		{
			throw new Exception(__METHOD__ . ": stream type '" . $protocol . "' is not available!");
		}

		// Return output stream
		return fopen($uri, 'r', false);
	}
}
