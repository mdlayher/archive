<?php

// Balancing algorithm which uses RNG to determine which server to target
class RandomBalancingAlgorithm implements IBalancingAlgorithm
{
	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	// Randomly determine a target server from array
	public function balance(array $servers)
	{
		// Randomly select a target
		return $servers[mt_rand(0, count($servers) - 1)];
	}
}
