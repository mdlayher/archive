<?php

// Balancing algorithm which pings servers and uses one with fastest response time
class FastestBalancingAlgorithm implements IBalancingAlgorithm
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - - -

	// The server determined by ping to be the fastest during this program run
	protected $fastest = null;

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - - -

	// Fastestly determine a target server from array
	public function balance(array $servers)
	{
		// If fastest server already found, return it
		if (isset($this->fastest))
		{
			return $this->fastest;
		}

		// Keep track of server with fastest ping time
		$bestTime = 5000;

		// Iterate and ping all servers to find fastest ping response
		foreach ($servers as $s)
		{
			// Ping server
			$ping = self::ping($s->host, $s->port);
			if (isset($ping) && $ping < $bestTime)
			{
				// Save best response time
				$bestTime = $ping;
				$this->fastest = $s;
			}
		}

		// Return fastest server
		return $this->fastest;
	}

	// Ping a server and return the response time in milliseconds
	protected static function ping($host, $port)
	{
		$start = microtime(true);
		if (!$socket = fsockopen($host, $port, $errno, $errstr, 1))
		{
			return null;
		}

		return round(((microtime(true) - $start) * 1000), 0);
	}
}
