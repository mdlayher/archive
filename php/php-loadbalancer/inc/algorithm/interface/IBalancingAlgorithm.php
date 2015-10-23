<?php

// The interface which all load balancing algorithms must implement
interface IBalancingAlgorithm
{
	// Apply balancing algorithm and return a resource URI
	public function balance(array $servers);
}
