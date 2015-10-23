<?php

class RoundRobinLoadBalancer extends AbstractLoadBalancer
{
	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Force usage of Round Robin load balancing algorithm
	public function __construct()
	{
		parent::__construct(new RoundRobinBalancingAlgorithm());
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	public function __toString()
	{
		return "Round Robin";
	}
}
