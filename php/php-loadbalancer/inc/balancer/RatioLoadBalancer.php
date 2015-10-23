<?php

class RatioLoadBalancer extends AbstractLoadBalancer
{
	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Force usage of Ratio load balancing algorithm
	public function __construct()
	{
		parent::__construct(new RatioBalancingAlgorithm());
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	// Override: add a server to load balancing pool, with an integer ratio as to how often it will be called during
	// round robin iteration
	public function add($protocol, $host, $port, $ratio = 1, $username = null, $password = null)
	{
		// Create a server, add server to pool
		$this->servers[] = new RatioBalancedServer($protocol, $host, $port, $ratio, $username, $password);
	}

	public function __toString()
	{
		return "Ratio";
	}
}
