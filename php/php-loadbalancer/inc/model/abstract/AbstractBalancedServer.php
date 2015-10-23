<?php

// The abstract balanced server from which all others derive
abstract class AbstractBalancedServer
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - - -

	// Server protocol, host, port
	public $protocol;
	public $host;
	public $port;

	// Optional: server username and password
	public $username;
	public $password;

	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Set up a server, optionally with username and password
	public function __construct($protocol, $host, $port, $username = null, $password = null)
	{
		$this->protocol = $protocol;
		$this->host = $host;
		$this->port = $port;

		if (isset($username, $password))
		{
			$this->username = $username;
			$this->password = $password;
		}
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - - -

	// Generate a resource URI when converted to string
	public function __toString()
	{
		// Add username and password if needed
		if (isset($this->username, $this->password))
		{
			return sprintf("%s://%s:%s@%s:%d/",
				$this->protocol, $this->username, $this->password, $this->host, $this->port
			);
		}

		return sprintf("%s://%s:%d/", $this->protocol, $this->host, $this->port);
	}
}
