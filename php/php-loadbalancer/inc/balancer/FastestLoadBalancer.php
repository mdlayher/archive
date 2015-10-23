<?php

class FastestLoadBalancer extends AbstractLoadBalancer
{
	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Force usage of Fastest load balancing algorithm
	public function __construct()
	{
		parent::__construct(new FastestBalancingAlgorithm());
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	public function __toString()
	{
		return "Fastest";
	}
}
