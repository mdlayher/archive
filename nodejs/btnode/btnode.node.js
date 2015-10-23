// btnode.node.js - Matt Layher, 4/1/2013
// Lightweight node.js BitTorrent tracker software, currently very pre-alpha.

// Application name
const APP_NAME = "btnode";

// Default port
const DEFAULT_PORT = 8080;
var port = DEFAULT_PORT;

// Parse and iterate command line arguments
var argv = process.argv.splice(2);
for (var i = 0; i < argv.length; i++)
{
	// Print help menu
	if (argv[i] === "-h" || argv[i] === "--help")
	{
		console.log("Usage: node " + APP_NAME + ".node.js [options] [arguments]\n");
		console.log("Options:");
		console.log("  -h, --help   print this help menu");
		console.log("  -p, --port   specify port to run tracker");
		process.exit(0);
	}
	// Set port
	else if (argv[i] === "-p" || argv[i] === "--port")
	{
		// Check for valid port, or use default if invalid
		if (argv[i+1] && is_int(argv[i+1]))
		{
			i++;
			port = parseInt(argv[i]);
		}
		else
		{
			port = DEFAULT_PORT;
		}
	}
	else
	{
		console.log(APP_NAME + ": invalid argument '" + argv[i] + "', run 'node APP_NAME.node.js -h' for help");
		process.exit(-1);
	}
}

// Integer validation (via regex)
function is_int(string)
{
	return /^\d+$/.test(string);
}

// Require express for routing
var app = require("express")();

// Require bencode for encoding/decoding data
var bencode = require("bencode");

// Configure express
// Set application title
app.set("title", APP_NAME);

// Announce route
app.get('/announce', function(req, res)
{
	// To announce, we check GET query string
	var query = req.query;

	// Check for mandatory parameters
	var mandatory = ["info_hash", "peer_id", "port", "uploaded", "downloaded", "left"];
	var missing = [];
	mandatory.forEach(function(field)
	{
		// If missing mandatory field, add to missing array
		if (typeof query[field] === "undefined")
		{
			missing.push(field);
		}
	});

	// Report any missing parameters to client
	if (missing.length > 0)
	{
		var msg = "";
		missing.forEach(function(field)
		{
			msg += field + " ";
		});
		res.end(APP_NAME + ": missing mandatory parameters: " + msg);
		return;
	}

	// IP is required, but may be present in GET request
	query.ip = typeof query.ip !== "undefined" ? query.ip : req.ip;

	// Check for optional fields: event, compact, no_peer_id
	query.event = typeof query.event !== "undefined" ? query.event : '';
	query.compact = typeof query.compact !== "undefined" ? query.compact : 0;
	query.no_peer_id = typeof query.no_peer_id !== "undefined" ? query.no_peer_id : 0;
	
	// Begin validation of all fields
	// info_hash and peer_id -> 20 characters
	var valid_str = ["info_hash", "peer_id"];
	valid_str.forEach(function(field)
	{
		if (query[field].length !== 20)
		{
			res.end(APP_NAME + ": invalid length: " + field);
			return;
		}
	});

	// All other mandatory integer fields
	var valid_int = ["port", "uploaded", "downloaded", "left"];

	valid_int.forEach(function(field)
	{
		if (!is_int(query[field]))
		{
			res.end("APP_NAME: invalid integer: " + field);
			return;
		}
	});

	// Much to do yet.
	res.end(APP_NAME + ": announce successful");
	return;
});

// Run it!
var app_event = app.listen(port);
app_event.on("error", function()
{
	// Check for low port, need elevated permissions
	if (port < 1024)
	{
		console.error(APP_NAME + ": permission denied, cannot bind to port " + port + ", exiting...");
	}
	else
	{
		console.error(APP_NAME + ": could not bind to port " + port + ", exiting...");
	}

	process.exit(-1);
});

// Print startup message
console.log(APP_NAME + ": initialized, running on port " + port);
