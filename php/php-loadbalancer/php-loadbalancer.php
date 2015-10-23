<?php
// php-loadbalancer autoloader

// Autoload all models
spl_autoload_register(function($class)
{
	// Recursively check and load all files under inc
	$iterator = new RecursiveDirectoryIterator(__DIR__ . "/inc/");
	foreach (new RecursiveIteratorIterator($iterator) as $filename => $file)
	{
		// Only check for PHP files
		if (basename($filename) == $class . ".php")
		{
			require_once $filename;
			break;
		}
	}
});
