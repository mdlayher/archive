<?php

// The abstract API client from which all others derive
abstract class AbstractApiClient
{
	// INSTANCE VARIABLES - - - - - - - - - - - - - - - - -

	// Server host and port
	protected $host;
	protected $port;

	// Server session key
	protected $session;

	// The current php-wavebox user's username
	protected $username;

	// CONSTRUCTOR - - - - - - - - - - - - - - - - - - - - -

	// Constructor to set hostname and port
	public function __construct($host, $port = 6500)
	{
		$this->host = $host;
		$this->port = $port;
	}

	// PUBLIC METHODS - - - - - - - - - - - - - - - - - - -

	// AUTHENTICATION METHODS - - - - - - - - - - - - - - -

	// Authentication via a username and password pair, optionally passing a client name
	public function login($username, $password, $client = "php-wavebox")
	{
		// Send login API request
		try
		{
			$response = $this->fetchJson(sprintf("login?u=%s&p=%s&c=%s", $username, $password, $client));
		}
		catch (Exception $e)
		{
			// On exception, server probably not available.
			return false;
		}

		// Store session key for use
		$this->session = $response["sessionId"];

		// Store username for later use
		$this->username = $username;

		// Return session
		return $this->session;
	}

	// Log out and delete this session from the WaveBox server
	public function logout()
	{
		// Send API logout request
		try
		{
			$response = $this->fetchJson("logout");
		}
		catch (Exception $e)
		{
			// On exception, server probably not available.
			return false;
		}

		return true;
	}

	// Authentication via a previously used session key, by default verifying its validity
	public function useSession($session, $verify = true)
	{
		// If no validity check needed, return now (good for multi API client scripts)
		$this->session = $session;
		if (!$verify)
		{
			return true;
		}

		try
		{
			// Send login API request to validate this session
			$response = $this->fetchJson(sprintf("login?s=%s", $session));
		}
		catch (Exception $e)
		{
			// On exception, server probably not available.
			return false;
		}

		// Check for null sessionId
		if (empty($response["sessionId"]))
		{
			return false;
		}

		return true;
	}

	// Search for query using specified, comma-separated types
	public function search($query, $type = null)
	{
		$url = sprintf("search?query=%s", $query);

		if (isset($type))
		{
			$url .= "&type=" . $type;
		}

		// Send API request to search for
		$response = $this->fetchJson($url);

		// Query response and object pairs to return
		$queries = array(
			"artists" => new Artist($this),
			"albums" => new Album($this),
			"songs" => new Song($this),
			"videos" => new Video($this),
		);

		// Iterate query response and add all matching objects
		$list = array();
		foreach ($queries as $key => $object)
		{
			if (isset($response[$key]))
			{
				$list[$key] = self::fieldsToList($response[$key], $object);
			}
		}

		return $list;
	}

	// Get a server status response from API
	public function getStatus($extended = false)
	{
		// Convert booleans to strings
		$extended = $extended ? "true" : "false";

		// Send API request for status
		return $this->fetchJson("status?extended=" . $extended)["status"];
	}

	// Used to copy the properties of this API client to another one, useful for calling methods in models
	public function copyTo($apiClient)
	{
		$newApiClient = new $apiClient($this->host, $this->port);
		$newApiClient->useSession($this->session, false);

		return $newApiClient;
	}

	// PRIVATE METHODS - - - - - - - - - - - - - - - - - - -

	// Fetch a binary stream for php-wavebox to proxy from WaveBox server
	protected function fetchBinaryStream($url)
	{
		// Set request headers
		$headers = array(
			"http" => array(
				"method" => "GET",
				"header" => "Accept: application/octet-stream\r\n",
			),
		);

		// Append wavebox_session cookie if available
		if (isset($this->session))
		{
			$headers["http"]["header"] .= "Cookie: wavebox_session=" . $this->session . "\r\n";
		}

		// Open binary stream
		$stream = fopen($url, "rb", false, stream_context_create($headers));

		// Verify a non-empty response was received
		if (empty($stream))
		{
			throw new Exception(__METHOD__ . ": failed to connect to WaveBox server!");
		}

		// Parse MIME type using special HTTP response variables, automatically generated by PHP
		$mime = "application/octet-stream";
		foreach ($http_response_header as $header)
		{
			// For whatever reason, this won't work with the leading "C".  Should be close enough.
			if (strpos($header, "ontent-Type:"))
			{
				// Strip key
				$mime = explode(' ', $header)[1];
			}
		}

		// Return packed as an array, so we can split into a list and keep MIME type
		return array($mime, $stream);
	}

	// Fetch array JSON response from a given API path, without need to pass session key or full URL
	protected function fetchJson($apiPath)
	{
		// Build URL path
		$url = sprintf("http://%s:%s/api/%s", $this->host, $this->port, $apiPath);

		// Set request headers
		$headers = array(
			"http" => array(
				"method" => "GET",
				"timeout" => 5,
				"header" => "Accept: application/json\r\nAccept-Encoding: gzip,deflate\r\n",
			),
		);

		// Append wavebox_session cookie if available
		if (isset($this->session))
		{
			$headers["http"]["header"] .= "Cookie: wavebox_session=" . $this->session . "\r\n";
		}

		// Make HTTP request, strip byte order mark
		$response = @file_get_contents($url, false, stream_context_create($headers));

		// Verify HTTP response received
		if (empty($http_response_header))
		{
			throw new Exception(__METHOD__ . ": failed to connect to WaveBox server!");
		}

		// Verify HTTP 200 response
		if ($http_response_header[0] !== "HTTP/1.1 200 OK")
		{
			throw new Exception(__METHOD__ . ": received bad HTTP reponse: " . $http_response_header[0]);
		}

		// Decompress and decode response into array
		$object = json_decode(self::decompressText($response, $http_response_header), true);

		// Verify a non-empty response was received
		if (empty($object))
		{
			throw new Exception(__METHOD__ . ": failed to connect to WaveBox server!");
		}

		// Throw any error responses as exceptions
		if (isset($object["error"]))
		{
			throw new Exception(__METHOD__ . ": " . $object["error"]);
		}

		return $object;
	}

	// Fetch a list of objects
	protected function fetchList($api, $object)
	{
		// Fetch a list of all objects from API
		$response = $this->fetchJson($api);

		// Make all keys lowercase
		$response = array_change_key_case($response);

		// Populate list of objects using API response
		return self::fieldsToList($response[$api], $object);
	}

	// Get a single object
	protected function getSingle($api, $id, $object)
	{
		// Send API request for a single object by ID
		$response = $this->fetchJson(sprintf("%s/%s", $api, $id));

		// Make all keys lowercase
		$response = array_change_key_case($response);

		// Populate object using API response
		return self::fieldsToSingle($response[$api][0], $object);
	}

	// STATIC METHODS - - - - - - - - - - - - - - - - - - -

	// Decompress a GZIP/DEFLATE response, detecting encoding from input headers
	protected static function decompressText($response, $headers)
	{
		// Parse content encoding using special HTTP response variables
		$encoding = null;
		foreach ($headers as $header)
		{
			// For whatever reason, this won't work with the leading "C".  Should be close enough.
			if (strpos($header, "ontent-Encoding:"))
			{
				// Strip key
				$encoding = explode(' ', $header)[1];
			}
		}

		// Decompress encoded data, if applicable
		if (isset($encoding))
		{
			if ($encoding == "gzip")
			{
				$response = @gzdecode($response);
			}
			else if ($encoding == "deflate")
			{
				$response = @gzinflate($response);
			}
		}
		else
		{
			// If not encoded, need to strip UTF8 byte order mark
			$response = @substr($response, 3);
		}

		// Check for bad response
		if (empty($response))
		{
			throw new Exception(__METHOD__ . ": received empty response from WaveBox server!");
		}

		return $response;
	}

	// Shortcut to convert API response fields to single object
	protected static function fieldsToSingle($fields, $object)
	{
		$o = clone $object;
		foreach ($fields as $key => $value)
		{
			$o->{$key} = $value;
		}

		return $o;
	}

	// Shortcut to convert API response fields to corresponding objects
	protected static function fieldsToList($fields, $object)
	{
		$list = array();
		foreach ($fields as $f)
		{
			$list[] = self::fieldsToSingle($f, $object);
		}

		return $list;
	}

	// Pass a stream directly to page, using specified mime type as header
	protected static function streamPassthru($mime, $stream)
	{
		// Pass appropriate headers and dump to page
		header("Content-Type: " . $mime);
		fpassthru($stream);
		fclose($stream);
	}
}