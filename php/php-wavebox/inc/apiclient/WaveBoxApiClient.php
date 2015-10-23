<?php

// EXPERIMENTAL universal API client, which generates and delegates work to the other API clients
class WaveBoxApiClient extends AbstractApiClient
{
	// A collection of all available API clients
	private $apiClients = array();

	// Used for initial setup, and to set basic properties on all API clients
	public function __construct($host, $port)
	{
		// Discover all API handlers, ignore self
		foreach (glob(__DIR__ . "/*ApiClient.php") as $client)
		{
			// Load client, grab classname for instantiation
			$class = substr(basename($client), 0, -4);
			if ($class == __CLASS__)
			{
				continue;
			}

			require_once $client;
			$this->apiClients[] = new $class($host, $port);
		}

		// Call parent constructor
		parent::__construct($host, $port);
	}

	// Used to authenticate with parent method, then store session
	public function login($username, $password, $client = "php-wavebox")
	{
		// Call parent login to verify
		$session = null;
		if (!$session = parent::login($username, $password, $client))
		{
			return false;
		}

		// On successful login, set sessions for all API clients, and don't verify
		return $this->useSession($session, false);
	}

	// Used to set session key for each API client
	public function useSession($session, $verify = false)
	{
		// Call parent useSession to verify
		if (!parent::useSession($session, true))
		{
			return false;
		}

		// Set session keys in all API clients, don't verify
		foreach ($this->apiClients as $client)
		{
			$client->useSession($session, false);
		}

		return true;
	}

	// Used as a "brute-force" way to call shortcut methods on API clients
	public function __call($name, $args)
	{
		// Iterate each API client
		foreach ($this->apiClients as $client)
		{
			// If named method is available, call it with arguments
			if (method_exists($client, $name))
			{
				return call_user_func_array(array($client, $name), $args);
			}
		}
	}
}
