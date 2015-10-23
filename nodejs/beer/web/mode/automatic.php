<?php
	// Beer Exchange - automatic.php - Matt Layher, 12/28/12
	// PHP script which performs price change based on recent purchases.  More purchases on a beer
	// will raise its price, and less will lower it.  Market crash will drop all to floor, randomize
	// on completion, and then repeat the algorithm
	//
	// changelog:
	// 12/28/12 MDL:
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
				// Check for crash in string form
				if ($_POST['crash'] === "true")
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
			$prices = $db->query("SELECT * FROM beer;");

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
					// FIX WHEN PRODUCTION DATA AVAILABLE


					// For testing, randomize quantity sold
					$p['quantity_sold'] = mt_rand(0, $p['quantity'] / 2);
			



					// Calculate ratio of beer sold vs remaining quantity
					$ratio = $p['quantity_sold'] / $p['quantity'];

					// Calculate delta in quarters of quantity sold and ratio
					$delta = ceil($ratio * $p['quantity_sold']) * 0.25;

					// If zero delta (no sales)
					if ($delta == 0.0)
					{
						// Ensure a $0.25 price drop to entice sales
						$delta = 0.25;
					}
						
					// Generate random chance number
					$chance = mt_rand(0,100);

					// If ratio is above 0.5, always raise
					if ($ratio >= 0.5)
					{
						$action = 2;
					}
					// If ratio is 0.0, always drop
					else if ($ratio == 0.0)
					{
						$action = 0;
					}
					else
					{
						// Else, do some math!
						// Calculate threshold to raise (all lower chances will raise)
						$raise_threshold = $ratio * 200;

						// Calculate threshold to keep (between raise and drop)
						$keep_threshold = 100 - ((100 - $raise_threshold) / 2);

						// Raise if number falls below threshold
						if ($chance < $raise_threshold)
						{
							$action = 2;
						}
						// Keep if number falls between thresholds
						else if ($chance <= $keep_threshold)
						{
							$action = 1;
						}
						// Drop if number is outside both thresholds
						else
						{
							$action = 0;
						}
					}
					
					// Set price using algorithm
					if ($action === 2)
					{
						// Raise price by delta
						$price = $p['price'] + $delta;
					}
					else if ($action === 1)
					{
						// Keep price
						$price = $p['price'];
					}
					else
					{
						// Drop price by delta
						$price = $p['price'] - $delta;
					}

					// Ensure price falls within min and max bounds
					if ($price > $p['price_max'])
					{
						$price = $p['price_max'];
					}
					else if ($price < $p['price_min'])
					{
						$price = $p['price_min'];
					}
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
