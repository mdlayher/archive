<?php

// Balancing algorithm which iterates all available servers in order
class RoundRobinBalancingAlgorithm implements IBalancingAlgorithm
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - - -

	// Index of server currently being used
	protected $index = 0;

	// Queue of servers to iterate in order
	protected $queue = array();

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	// Balance servers by iterating through each in order
	public function balance(array $servers)
	{
		// If queue not already set, take input, otherwise it is ignored because state is preserved
		// in this class by the algorithm
		if (empty($this->queue))
		{
			$this->queue = $servers;
		}

		// Select server, increment index upon retrieval
		$target = $this->queue[$this->index++];

		// If index has reached end of queue, restart index
		if ($this->index == count($this->queue))
		{
			$this->index = 0;
		}

		return $target;
	}
}
