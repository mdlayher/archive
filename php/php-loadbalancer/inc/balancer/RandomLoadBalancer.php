<?php

class RandomLoadBalancer extends AbstractLoadBalancer
{
	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Force usage of Random load balancing algorithm
	public function __construct()
	{
		parent::__construct(new RandomBalancingAlgorithm());
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	public function __toString()
	{
		return "Random";
	}
}
