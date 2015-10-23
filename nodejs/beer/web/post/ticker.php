<?php
	// Open database connection
	require_once "../inc/db.inc.php";
	$db = pdo_open();

	// Query for list of beers with prices (and list price to calculate diff)
	$beers = $db->query("SELECT name,price,price_last FROM beer;");

	// Begin generating ticker output
	$ticker = "<span class=\"marquee\">";
	while ($b = $beers->fetch())
	{
		// Calculate diff
		$diff = $b['price'] - $b['price_last'];

		// If higher price...
		if ($diff > 0)
		{
			$ticker .= sprintf("%s (<span class=\"green\">&#9650;$%s</span>) | ", $b['name'], number_format(abs($diff), 2));
		}
		// Else, if lower price...
		else if($diff < 0)
		{
			$ticker .= sprintf("%s (<span class=\"red\">&#9660;$%s</span>) | ", $b['name'], number_format(abs($diff), 2));
		}
		// Else, if equal...
		else
		{
			$ticker .= sprintf("%s (NC) | ", $b['name']);
		}
	}
	$ticker .= "</span>";

	// Send ticker span
	echo $ticker;

	// Close database connection
	$db = null;

	// End script
	return;
?>
