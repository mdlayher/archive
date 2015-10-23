// Key required for successful authentication
const AUTH_KEY = "myfakekey";

var mhash = require('mhash');
var sqlite = require('sqlite3');

function beer_automatic(obj)
{
	// Ensure key match
	if (obj.key === mhash("sha256", obj.key))
	{
		// Determine if market crash should occur
		var crash = false;

		// Check for forced crash
		if (obj.crash)
		{
			crash = true;
		}
		else
		{
			// Set 10% chance of crash
			crash = Math.floor((Math.random() * 10) + 1) === 7 ? true : false;
		}

		// Open database connection
		var db = new sqlite.Database('beer.sqlite');

		// 

	}
}
