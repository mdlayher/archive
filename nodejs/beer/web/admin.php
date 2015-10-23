<?php
	// Configuration
	$page_title = "Beer Exchange Admin";

	// Fetch secret key from node.js
	$auth_key = file_get_contents('http://localhost:8081/');
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $page_title ?></title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
		<meta name="robots" content="noindex" />
		<link rel="shortcut icon" type="image/icon" href="img/favicon.ico" />
		<link href="css/main.css" rel="stylesheet" type="text/css" />
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script src="js/jquery.simplemarquee.js"></script>
		<script src="js/jquery.blink.js"></script>
		<script src="http://servnerr.com:8080/socket.io/socket.io.js"></script>
		<script type="text/javascript">
			var socket = null;

			// Object to be passed in to administrative requests
			var obj = { };
				obj.key = "<?php echo $auth_key ?>";

			// On document ready...
			$(function() {
				// Hide content by default
				$('#content').hide();
				$('#info').hide();

				// Establish socket connection
				try
				{
					socket = io.connect('http://servnerr.com:8080');
					socket.emit('init', null);
				}
				catch(e)
				{
					$('#status').text("Development in progress...");
				}

				// Built-in handlers
				// On socket connection, show content
				socket.on('connect', function()
				{
					$('#content').show();
					$('#info').show();
				});

				// When connection dies, attempt reconnect
				socket.on('reconnecting', function()
				{
					// Hide content and info
					$('#content').hide();
					$('#info').hide();
					
					// Iterate until reconnected
					var retry
					socket.once('reconnect', function()
					{
						clearInterval(retry);
						socket.emit('init', null);
						$('#status').text('');
					});
					
					// Keep track of number of tries
					var count = 0;
					retry = setInterval(function()
					{
						// Display reconnect "progress"
						var dots = "";
						for (var i = 0; i < count; i++)
						{
							dots += ". ";

							if (count == 6)
							{
								count = 0;
							}
						}

						// Display reconnect status
						count++;
						$('#status').text("reconnecting " + dots);
					}, 1000);
				});

				// Custom handlers
				// Keep track of market crash
				socket.on('crash', function()
				{
					$('#status').text("MARKET CRASH!");
					$('#status').blink();
				});

				// Keep track of current period
				socket.on('period', function(period)
				{
					period = (period < 10 ? "0" : "") + period;
					$('#period').text(period);
				});

				// Display status from socket messages
				socket.on('status', function(msg)
				{
					$('#status').text(msg);
				});

				// Keep track of time as established by server
				socket.on('timer', function(time)
				{
					// Convert to minutes and seconds
					var min = Math.floor(time / 60);
					min = (min < 10 ? "0" : "") + min;
					var sec = time % 60;
					sec = (sec < 10 ? "0" : "") + sec;

					$('#timer').text(min + ":" + sec);
				});

				// Administrative triggers to server
				// Toggle a forced crash
				$('#crash').click(function()
				{
					socket.emit('crash', obj);
				});

				// Trigger a forced update
				$('#update').click(function()
				{
					socket.emit('update', obj);
				});
			});
		</script>
	</head>
	<body>
		<!-- Header, period and timer -->
		<h1><?php echo $page_title ?> <span id="info">- [#<span id="period"></span>] [<span id="timer"></span>]</span></h1>
		<hr />
		<!-- Status bar -->
		<h2 id="status"></h2>
		<!-- Page content -->
		<div id="content">
			<input type="button" id="update" value="Force Update" />
			<input type="button" id="crash" value="Toggle Crash" />
		</div>
	</body>
</html>
