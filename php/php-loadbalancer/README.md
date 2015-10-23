php-loadbalancer
================

PHP 5.4+ library which enables intelligent, software load-balancing, using a variety of algorithms.  MIT Licensed.

Example
-------

Several load balancing algorithms are available through use of this library.  Here are a few examples:

```php
<?php
// php-loadbalancer - Example basic usage script
require_once __DIR__ . "/php-loadbalancer.php";

// Create an array of load balancers
$balancers = array(
	// Random - picks a server out of the queue using random number generator
	new RandomLoadBalancer(),

	// Round Robin - picks servers out of the queue in order, continuously
	// looping through the queue
	new RoundRobinLoadBalancer(),

	// Ratio - utilizes Round Robin, but will use the same server multiple
	// times according to an integer weight
	new RatioLoadBalancer(),

	// Fastest - pings all servers in queue and uses server with lowest ping
	// for all calls to balance() on this program run
	new FastestLoadBalancer(),
);

// Iterate and run all balancers
foreach ($balancers as $b)
{
	// Add a ratio if needed
	if ($b instanceof RatioLoadBalancer)
	{
		// Use Google 3x as often as Yahoo
		$b->add("http", "google.com", 80, 3);
		$b->add("http", "yahoo.com", 80, 1);
	}
	else
	{
		// Basic usage
		$b->add("http", "google.com", 80);
		$b->add("http", "yahoo.com", 80);
	}

	// Run 5 times, allow algorithm to determine which server should be used as target
	printf("algorithm: %s\n", $b);

	for ($i = 0; $i < 5; $i++)
	{
		printf("\t- %s\n", $b->balance());
		// - http://google.com:80/, etc
	}

	printf("\n");
}
```
