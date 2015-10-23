<?php

// Storage for properties of a server with load ratio to be balanced by load balancer
class RatioBalancedServer extends AbstractBalancedServer
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - - - -

	// Number of hits to this server before it should be switched out
	public $ratio;

	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - - -

	// Set up a server, optionally with username and password
	public function __construct($protocol, $host, $port, $ratio, $username = null, $password = null)
	{
		// Invoke parent
		parent::__construct($protocol, $host, $port, $username, $password);

		// Set ratio
		$this->ratio = $ratio;
	}
}
