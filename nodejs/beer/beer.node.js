// beer.node.js - Matt Layher, 12/27/2012
// Core server for Beer Exchange clone.  Keeps all clients synchronized with its clock, and pushes
// beer and price updates to all attached clients simultaneously
//
// changelog:
// 2/1/13 MDL:
//  - minor fixes, beginning migration of PHP/MySQL helpers to node.js/sqlite
// 1/3/13 MDL:
//	- reorganization and creation of functions for common (or large) features
// 1/2/13 MDL:
//	- added triggers for firing events
//	- added micro HTTP server to advertise key for administrative interface
// 12/28/12 MDL:
//	- console control
//	- automatic algorithm
// 12/27/12 MDL:
//	- cache and push model for updates
//	- separate time period for crashes
//	- command line arguments
//	- multiple modes enabled
//	- authentication for price change and retrieval
// 12/26/12 MDL:
//	- initial code

// CONFIGURATION - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Constants
// Debug
const DEBUG = true;
// Default time period (15 minutes)
const DEFAULT_TIME_START = 60 * 15;
// Default time period for market crash (5 minutes)
const DEFAULT_TIME_CRASH = 60 * 5;

// Default SQLite database
//const DEFAULT_DB_FILE = "beer.sqlite";

// Secret authorization key
const AUTH = "myfakekey";

// (Pseudo-constant) List of available modes
var MODES = { };
	MODES.random = "random";
	MODES.automatic = "automatic";
// Mode in use
const DEFAULT_MODE = "automatic";

// "Global" variables
// Defaults for time period, crash time period (can be overridden by arguments)
var TIME_START = DEFAULT_TIME_START;
var TIME_CRASH = DEFAULT_TIME_CRASH;

// Default database file (can be overriden by arguments)
//var DB_FILE = DEFAULT_DB_FILE;

// Global clock timer
var TIME;

// Global period counter
var PERIOD = 0;

// Defaults for running mode (can be overridden by arguments)
var MODE = MODES[DEFAULT_MODE];

// Enable verbose mode
var VERBOSE = false;

// Create global cache, used to push data to clients
var CACHE = { };

// Determine if enforcing a panic mode
var PANIC = false;

// Determine if stopping at end of period
var FORCE_STOP = false;

// Determine if crash occurs next period
var FORCE_CRASH = false;

// Determine if active crash
var ACTIVE_CRASH = false;

// STARTUP - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Import required libraries
var httpio = require('socket.io').listen(8080);
var qs = require('querystring');
var http = require('http');

// Import mhash for hashing
try
{
	var mhash = require('mhash').hash;
}
catch (e)
{
	console.log("[beer.node.js] - error: mhash not found, install using 'npm install mhash'");
	process.exit(-1);
}

/*
// Import sqlite3 for database
try
{
	var sqlite = require('sqlite3');
	var db = new sqlite.Database(DB_FILE);
}
catch (e)
{
	console.log("[beer.node.js] - error: sqlite3 not found, install using 'npm install sqlite3'");
	process.exit(-1);
}
*/

// Parse command line arguments
parse_argv();

// SOCKET SETUP - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// On client connection, pass off to socket handler
httpio.on('connection', function(socket)
{
	socket_handler(socket);
});

// Configure socket.io to be silent
httpio.configure(function()
{
	httpio.set('log level', 0);
	httpio.set('match origin protocol', true);
});

// INITIALIZATION - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Print startup messages
console.log("[beer.node.js] starting... [time: " + TIME_START + "s (" + (TIME_START / 60) + "m) | crash_time: " + TIME_CRASH + "s (" + (TIME_CRASH / 60) + "m)]");
console.log("[beer.node.js] mode: " + MODE);
if (VERBOSE)
{
	console.log("[beer.node.js] verbose mode enabled");
}

// Open stdin for console input
var stdin = process.openStdin();
stdin.on('data', function(chunk)
{
	stdin_handler(chunk);
});

// Generate authentication key
logger("[init] initializing");
var auth_key = mhash("sha256", AUTH);
logger("[init] authentication key generated");

// Start HTTP server to advertise key to local clients
http.createServer(function(req, res)
{
	res.writeHead(200, { 'Content-Type': 'text/plain' });
	res.end(auth_key);
}).listen(8081);
logger("[init] HTTP key server started");

// Generate default POST data
var POST_DATA = {
	key: auth_key,
	crash: FORCE_CRASH
}

// Update prices using randomization algorithm
post_handler("/beer/mode/" + MODES.random + ".php", "status", POST_DATA);
logger("[init] price randomization complete");

// Retrieve and cache most recent data on startup
post_handler("/beer/post/chart.php", "chart");
post_handler("/beer/post/ticker.php", "ticker");
logger("[init] data fetch into cache complete");

// End init
logger("[init] initialization complete");

// RUN - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Initialize timer
TIME = TIME_START;

// Start period counter
PERIOD++;

// Core timer countdown function
setInterval(function()
{
	// "Predict" market crash if one is forced
	if (TIME === 30 && FORCE_CRASH)
	{
		httpio.sockets.in('beer').emit('status', "Market crash predicted...");
	}
	// Perform price update (given a few extra seconds to ensure completion)
	if (TIME === 3)
	{
		// If crash requested, add triggering data
		POST_DATA.crash = FORCE_CRASH;

		// Reset force crash so we can't crash twice in a row
		if (ACTIVE_CRASH)
		{
			// Disable force crash	
			FORCE_CRASH = false;
			POST_DATA.crash = FORCE_CRASH;
		}
		
		// Perform specified price change algorithm
		post_handler("/beer/mode/" + MODE + ".php", "status", POST_DATA);
		logger("[update] price change complete");
	}
	// Fetch updates into cache
	if (TIME === 1)
	{
		// Get status from price update
		console.log("[update] [p: " + PERIOD + "] [m: " + MODE + "] status: " + CACHE.status);

		// Retrieve latest data for chart and ticker
		post_handler("/beer/post/chart.php", "chart");
		post_handler("/beer/post/ticker.php", "ticker");
		logger("[fetch] data fetch into cache complete");
	}
	// Push updates from cache
	if (TIME === 0)
	{
		// On force stop, exit here!
		if (FORCE_STOP)
		{
			console.log("[beer.node.js] period complete, stopping now!");
			process.exit(0);
		}

		// Reset crash status
		ACTIVE_CRASH = false;

		// Fire and increment trading period
		PERIOD++;
		httpio.sockets.in('beer').emit('period', PERIOD);

		// Trigger an update for all clients
		httpio.sockets.in('beer').emit('update', CACHE);

		// If crash occurred, send message and shorten timer
		if (CACHE.status === "crash")
		{
			// Trigger crash notification
			ACTIVE_CRASH = true;
			console.log("[push] market crash triggered!");
			httpio.sockets.in('beer').emit('crash', true);

			// Add 1s to account for slight delay
			TIME = parseInt(TIME_CRASH) + 1;
		}
		else
		{
			// Else, reset timer to default (add 1s to account for slight delay)
			TIME = parseInt(TIME_START) + 1;
		}

		// Reset update status
		logger("[push] update push complete, time reset to " + (TIME - 1) + "s");
	}

	// Decrement timer, enforce panic, repeat
	TIME--;
	httpio.sockets.in('beer').emit('panic', PANIC);
	httpio.sockets.in('beer').emit('timer', TIME);
}, 1000);

// FUNCTIONS - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Unified logging
function logger(string)
{
	// Log to file?
	
	// If verbose, output to console
	if (VERBOSE)
	{
		console.log(string);
	}
}

// Parse and set command line arguments
function parse_argv()
{
	if (DEBUG)
	{
		console.log("parse_argv()");
	}

	// Retrieve "argv" arguments from node.js
	var argv = process.argv.splice(2);

	// Iterate command line arguments
	for (var i = 0; i < argv.length; i++)
	{
		// Print help menu
		if (argv[i] === "-h" || argv[i] === "--help")
		{
			console.log("Usage: node beer.node.js [options] [arguments]\n");
			console.log("Options:");
			console.log("  -h, --help	  print this menu");
			console.log("  -m, --mode	  select operation mode [random, automatic]");
			console.log("  -t, --time     set time period");
			console.log(" -tc, --timec    set crash time period");
			console.log("  -v, --verbose  enable console logging");
			process.exit(0);
		}
		// Set operations mode
		else if (argv[i] === "-m" || argv[i] === "--mode")
		{
			// Use next argument to set mode, default if argument null
			if (argv[i+1])
			{
				// Skip to value
				i++;
				
				// Validate mode type
				if (argv[i] === MODES.random || argv[i] === MODES.automatic)
				{
					// Set specified mode
					MODE = MODES[argv[i]];
				}
				else
				{
					// Default on bad mode type
					MODE = MODES[DEFAULT_MODE]
				}
			}
			else
			{
				// Default to default mode
				MODE = DEFAULT_MODE;
			}
		}
		// Set default and crash time periods
		else if (argv[i] === "-t" || argv[i] === "-tc" || argv[i] === "--time" || argv[i] === "--timec")
		{
			// Store argument
			var arg = argv[i];
		
			// Use next argument to set time, default if argument null
			if (argv[i+1])
			{
				// Skip to value
				i++;

				// Validate as integer, ensure time is greater than 10 seconds
				if ((argv[i] == parseInt(argv[i])) && (parseInt(argv[i]) >= 10))
				{
					// Set specified time in seconds
					if (arg === "-t" || arg === "--time")
					{
						TIME_START = argv[i];
					}
					else
					{
						// Else, set specified crash time in seconds
						TIME_CRASH = argv[i];
					}
				}
				else
				{
					// If value doesn't validate, set both to defaults
					console.log("[beer.node.js] - error: time too short (< 10s) or invalid, using defaults");
					TIME_START = DEFAULT_TIME_START;
					TIME_CRASH = DEFAULT_TIME_CRASH;
				}
			}
		}
		// Enable verbose console logging
		else if (argv[i] === "-v" || argv[i] === "--verbose")
		{
			// Set verbose mode true
			VERBOSE = true;
		}
		else
		{
			// Else, invalid argument
			console.log("[beer.node.js] - error: invalid argument '" + argv[i] + "'");
			process.exit(-1);
		}
	}
}

// Send POST request to target, put result into cache key
function post_handler(handler, cache_key, data)
{
	if (DEBUG)
	{
		console.log("post_handler(" + handler + ", " + cache_key + ", " + data + ")");
	}

	// Generate POST data from input object
	var post_data = qs.stringify(data);

	// Set POST options and destination
	var post_options = {
		host: "servnerr.com",
		port: 80,
		path: handler,
		method: "POST",
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			'Content-Length': post_data.length
		}
	}

	// Initialize a POST request
	var post_req = http.request(post_options, function(response)
	{
		// Capture UTF-8 response
		response.setEncoding('utf8');
		var resp_str = '';

		// When data is received, add the data chunk
		response.on('data', function(chunk)
		{
			resp_str += chunk;
		});

		// On data complete, cache the received response
		response.on('end', function()
		{
			CACHE[cache_key] = resp_str;
		});

		// On data error, log error
		response.on('error', function()
		{
			console.log("[post] error occurred!");
			console.log("[post] response: " + resp_str);
		});
	});

	// Send POST request
	post_req.write(post_data);
	post_req.end();
}

// Handle all socket events fired
function socket_handler(socket)
{
	// User handlers
	// INIT - used to initialize clients with all current data (always fire first!)
	socket.on('init', function(obj)
	{
		// Join them to the 'beer' room, so we can perform other functions
		socket.join('beer');
		logger("[info] new client connected");

		// Send client current timer
		socket.emit('timer', TIME);

		// Send client current period
		socket.emit('period', PERIOD);

		// Send client current cached data
		socket.emit('update', CACHE);

		// Trigger crash notification if one is active
		if (ACTIVE_CRASH)
		{
			socket.emit('crash', true);
		}
	});

	// Administrative handlers (require correct auth_key)
	// CRASH - used to toggle a forced market crash next period
	socket.on('crash', function(obj)
	{
		// Verify correct key sent to trigger
		if (obj.key === auth_key)
		{
			// Toggle crash status
			FORCE_CRASH = !FORCE_CRASH;
			if (FORCE_CRASH)
			{
				console.log("[admin] force crash enabled, will crash next period");
			}
			else
			{
				console.log("[admin] force crash disabled, will not crash next period");
			}
		}
		else
		{
			console.log("[admin] could not toggle force crash, invalid key");
		}
	});

	// HALT - used to immediately terminate the program
	socket.on('halt', function(obj)
	{
		// Verify correct key sent to trigger
		if (obj.key === auth_key)
		{
			console.log("[admin] halting!");
			process.exit(0);
		}
		else
		{
			console.log("[admin] could not halt, invalid key");
		}
	});

	// PANIC - used to toggle the panic mode
	socket.on('panic', function(obj)
	{
		// Verify correct key sent to trigger
		if (obj.key === auth_key)
		{
			// Toggle panic mode
			PANIC = !PANIC;
			if (PANIC)
			{
				console.log("[admin] panic mode enabled, all screens blacked out");
			}
			else
			{
				console.log("[admin] panic mode disabled, all screens resumed");
			}
		}
		else
		{
			console.log("[admin] could not toggle panic, invalid key");
		}
	});

	// STOP - used to toggle a forced stop after this period
	socket.on('stop', function(obj)
	{
		// Verify correct key sent to trigger
		if (obj.key === auth_key)
		{
			// Toggle force stop
			FORCE_STOP = !FORCE_STOP;
			if (FORCE_STOP)
			{
				console.log("[admin] force stop enabled, will stop next period");
			}
			else
			{
				console.log("[admin] force stop disabled, will not stop next period");
			}
		}
		else
		{
			console.log("[admin] could not force stop, invalid key");
		}
	});

	// UPDATE - used to drop timer to lowest possible interval, triggering a quick update
	socket.on('update', function(obj)
	{
		// Verify correct key sent to trigger
		if (obj.key === auth_key)
		{
			console.log("[admin] force update triggered, setting time to 5 seconds");
			TIME = 5;
		}
		else
		{
			console.log("[admin] could not trigger force update, invalid key");
		}
	});
}

// Handle stdin (console) data processing
function stdin_handler(chunk)
{
	// Split into array
	var chunk = chunk.toString();
	var input = chunk.substring(0, chunk.length -1).split(' ');
	var i = 0;

	// Clear console
	if (input[i] === "clear")
	{
		// Linux trick to clear console
		console.log('\033[2J');	
	}
	// Toggle forced crash
	else if (input[i] === "crash")
	{
		// Toggle crash status
		FORCE_CRASH = !FORCE_CRASH;
		if (FORCE_CRASH)
		{
			console.log("[console] force crash enabled, will crash next period");
		}
		else
		{
			console.log("[console] force crash disabled, will not crash next period");
		}
	}
	// Display a help menu
	else if (input[i] === "help")
	{
		console.log("[beer.node.js] - Matt Layher 2012-2013");
		console.log("commands:");
		console.log("  clear	clear this console");
		console.log("  crash	force a crash next period");
		console.log("   halt	immediately stop server");
		console.log("   mode	change operation mode");
		console.log("    msg	display a message on client status bar");
		console.log("  panic	black out all client displays");
		console.log("   stat	display current state of server variables");
		console.log("   stop	force server to stop next period");
		console.log("   time	display or set the current period time");
		console.log(" update	force a price update");
		console.log("verbose	display verbose console logging");
	}
	// Check for force halt
	else if (input[i] === "halt")
	{
		// Immediately halt and exit
		console.log("[console] halting!");
		process.exit(0);
	}
	// Check for mode change
	else if (input[i] === "mode")
	{
		// Check for argument
		if (input[i+1])
		{
			// Increment index
			i++;

			// Check for valid mode type (cannot swap to manual now)
			if (input[i] == MODES.random || input[i] == MODES.automatic)
			{
				// Set specified mode
				MODE = MODES[input[i]];
				console.log("[console] switching to mode: " + MODE);
			}
			else
			{
				console.log("[console] invalid mode specified, no change");
			}
		}
		else
		{
			// Display current mode
			console.log("[console] current mode: " + MODE);
		}
	}
	// Send a message to the status bar on clients
	else if (input[i] === "msg")
	{
		// Check for argument
		if (input[i+1])
		{
			// Increment index
			i++;

			// Iterate remaining arguments to process message
			var message = "";
			for (var j = i; j < input.length; j++)
			{
				message += input[j] + " ";
			}

			// Send message to clients
			httpio.sockets.in('beer').emit('status', message);
		}
		else
		{
			// Clear status on clients
			httpio.sockets.in('beer').emit('status', '');
		}
	}
	// Toggle a panic on the displays
	else if (input[i] === "panic")
	{
		// Toggle panic mode
		PANIC = !PANIC;
		if (PANIC)
		{
			console.log("[console] panic mode enabled, all screens blacked out");
		}
		else
		{
			console.log("[console] panic mode disabled, all screens resumed");
		}
	}
	// Display current program status
	else if (input[i] === "stat")
	{
		console.log("[beer.node.js] - Matt Layher, 2012-2013");
		console.log("[console]   TIME_START : " + TIME_START);
		console.log("[console]   TIME_CRASH : " + TIME_CRASH);
		console.log("[console]         MODE : " + MODE);
		console.log("[console]         TIME : " + TIME);
		console.log("[console]       PERIOD : " + PERIOD);
		console.log("[console]      VERBOSE : " + VERBOSE);
		console.log("[console]        PANIC : " + PANIC);
		console.log("[console]   FORCE_STOP : " + FORCE_STOP);
		console.log("[console]  FORCE_CRASH : " + FORCE_CRASH);
		console.log("[console] ACTIVE_CRASH : " + ACTIVE_CRASH);
	}
	// Toggle force stop
	else if (input[i] === "stop")
	{
		// Toggle force stop
		FORCE_STOP = !FORCE_STOP;
		if (FORCE_STOP)
		{
			console.log("[console] force stop enabled, will stop next period");
		}
		else
		{
			console.log("[console] force stop disabled, will not stop next period");
		}
	}
	// Display/modify time remaining in period
	else if (input[i] === "time")
	{
		// If further input exists...
		if (input[i+1])
		{
			i++;
			
			// Validate as integer, ensure time is greater than 10 seconds
			if ((input[i] == parseInt(argv[i])) && (parseInt(input[i]) >= 10))
			{
				TIME = input[i];
			}
			else
			{
				// Else, invalid time, display error
				console.log("[beer.node.js] - error: time too short (< 10s) or invalid");
			}
		}
		else
		{
			// Else, display current remaining time
			console.log("[console] " + TIME + " seconds remain");
		}
	}
	// Force an update (drop timer very low to finish clock cycle)
	else if (input[i] === "update")
	{
		console.log("[console] force update triggered, setting time to 5 seconds");
		TIME = 5;
	}
	// Toggle verbose mode
	else if (input[i] === "verbose")
	{
		// Toggle verbose mode
		VERBOSE = !VERBOSE;
		if (VERBOSE)
		{
			console.log("[console] verbose mode enabled");
		}
		else
		{
			console.log("[console] verbose mode disabled");
		}
	}
	else
	{
		// Print console help
		console.log("[console] invalid command");
	}
}
