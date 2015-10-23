<?php
	// Beer Exchange - chart.php - Matt Layher, 12/26/12
	// PHP file which returns a POST response containing a formatted table of beers with prices
	//
	// changelog:
	// 12/28/12 MDL:
	//	- enabled table to be displayed using CSS3 columns
	// 12/27/12 MDL:
	//	- added arrow symbols indicating higher or lower price than last period
	// 12/26/12 MDL:
	//	- initial code
	
	// Enable error reporting
	error_reporting(E_ALL);
		
	// Open database connection
	require_once "../inc/db.inc.php";
	$db = pdo_open();

	// Query for list of beers with prices
	$beers = $db->query("SELECT name,price,price_last FROM beer;");

	// Begin generating output table
	$table = "<table class=\"right\">";
	while ($b = $beers->fetch())
	{
		// Calculate diff
		$diff = $b['price'] - $b['price_last'];

		// Format price
		$price = '$' . number_format($b['price'], 2);
		
		// If higher price...
		if ($diff > 0)
		{
			$price = sprintf("<span class=\"green\">&#9650;%s</span>", $price);
		}
		// Else, if lower price...
		else if($diff < 0)
		{
			$price = sprintf("<span class=\"red\">&#9660;%s</span>", $price);
		}

		// Shrink name via CSS if it's too long
		$name = $b['name'];
		if (strlen($name) >= 20)
		{
			$name = sprintf("<span class=\"small\">%s</span>", $name);
		}

		// Generate output cells
		$table .= sprintf("<tr><td class=\"name\">%s</td><td class=\"price\">%s</td></tr>", $name, $price);
	}
	$table .= "</table>";
	
	// Send beer table
	echo $table;

	// Close database connection
	$db = null;

	// End script
	return;
?>
