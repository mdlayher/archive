<?php
	// Beer Exchange - random.php - Matt Layher, 12/26/12
	// PHP script which performs a random price change on all beers in the database
	//
	// changelog:
	// 12/27/12 MDL:
	//	- authentication via secret key and hash
	// 12/26/12 MDL:
	//	- initial code
	
	// Enable error reporting
	error_reporting(E_ALL);
	
	// Key required for successful authentication
	const AUTH_KEY = "myfakekey";

	// Ensure a key is set
	if (isset($_POST['key']))
	{
		// Ensure key match so prices cannot be modified without it
		if ($_POST['key'] === hash("sha256", AUTH_KEY))
		{
			// Determine if market crash occurred
			$crash = false;

			// Check if a market crash header was sent
			if (isset($_POST['crash']))
			{
				// Check for true in string form
				if($_POST['crash'] === "true")
				{
					$crash = true;
				}
			}
			else
			{
				// Randomly determine if crash occurs (10% chance)
				$crash = rand(0,9) === 7 ? true : false;
			}

			// Open database connection
			require_once "../inc/db.inc.php";
			$db = pdo_open();

			// Grab current price lists
			$prices = $db->query("SELECT id,price,price_last,price_min,price_max FROM beer;");

			// Iterate lists
			while ($p = $prices->fetch())
			{
				// Store current price as last price
				$last = $p['price'];

				// If crash, drop price to its minimum
				if ($crash)
				{
					$price = $p['price_min'];	
				}
				else
				{
					// Else, calculate new price between min and max, round up to nearest quarter
					$price = (round(mt_rand($p['price_min'] * 10, $p['price_max'] * 10) / 10 * 4) / 4);
				}

				// Execute query to update prices
				$stmt = $db->prepare("UPDATE beer SET price=?,price_last=? WHERE id=?;");
				$stmt->execute(array($price, $last, $p['id']));
			}

			// Terminate database connection
			$db = null;

			// Send return message
			if ($crash)
			{
				// 'crash' will trigger a crash server-side
				echo "crash";
			}
			else
			{
				// success is normal
				echo "success";
			}

			// Terminate script
			return;
		}
		// Echo key mismatch to POSTer on failure
		echo "error: key mismatch";
	}
	else
	{
		// Echo no key set to POSTer on failure
		echo "error: no key set";
	}
?>
