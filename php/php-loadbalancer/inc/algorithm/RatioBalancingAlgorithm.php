<?php

// Balancing algorithm which iterates all available servers in order, and uses a predefined ratio to determine
// number of hits a server may receive before it is swapped out with the next in the queue
class RatioBalancingAlgorithm extends RoundRobinBalancingAlgorithm implements IBalancingAlgorithm
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - - -

	// Number of hits to particular server, to be used with ratio in order to balance load
	protected $hits = 0;

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	// Balance servers by iterating through each in order, using weighted ratios to further balance load
	public function balance(array $servers)
	{
		// If queue not already set, take input, otherwise it is ignored because state is preserved
		// in this class by the algorithm
		if (empty($this->queue))
		{
			$this->queue = $servers;
		}

		// Select server, increment hits
		$target = $this->queue[$this->index];
		$this->hits++;

		// If number of hits matches defined ratio, increment index
		if ($this->hits == $target->ratio)
		{
			$this->hits = 0;
			$this->index++;
		}

		// If index has reached end of queue, restart index
		if ($this->index == count($this->queue))
		{
			$this->index = 0;
		}

		return $target;
	}
}
