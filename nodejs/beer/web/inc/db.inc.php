<?php
	// Beer Exchange - db.inc.php - Matt Layher, 12/26/12
	// PHP include file which provides PDO database access via a common function
	//
	// changelog:
	// 12/26/12 MDL:
	//	- initial code
	
	// Enable all error reporting
	error_reporting(E_ALL);

	// Access database using PDO
	function pdo_open()
	{
		return new PDO("mysql:host=localhost;dbname=beer;", "beer", "h4VS4auu3PVyj9LM");
	}
?>
