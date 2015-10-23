<?php

// The abstract API client from which all ApiClients which use models which extend AbstractMediaItem derive
class AbstractMediaItemApiClient extends AbstractItemApiClient
{
	// Get a URL path for a file stream
	public function getStreamLink($id)
	{
		// Build URL path
		return sprintf("http://%s:%s/api/stream/%s?s=%s", $this->host, $this->port, $id, $this->session);
	}

	// Get a URL path for a transcoded file stream
	public function getTranscodeLink($id, $quality = "medium", $codec = null)
	{
		// Check for valid quality
		$validQuality = array("low", "medium", "high", "extreme");
		if (!in_array($quality, $validQuality) && !is_int($quality))
		{
			throw new Exception(__METHOD__ . ": quality setting must be 'low', 'medium', 'high', 'extreme', or a valid integer!");
		}

		// Build URL path, using default codec
		$url = sprintf("http://%s:%s/api/transcode/%s?transQuality=%s&s=%s",
			$this->host, $this->port, $id, $quality, $this->session);

		// For the time being, ignore codec
		/*
		// Pass transcoding codec if requested
		if (isset($codec))
		{
			$url .= "&transType=" . $codec;
		}
		*/

		return $url;
	}

	// Output a transcode stream directly to the page (used to proxy transcodes from WaveBox, hiding session key)
	public function outputTranscodeStream($id, $quality = "medium", $codec = "mp3")
	{
		// Fetch MIME type and stream
		list($mime, $stream) = $this->fetchBinaryStream($this->getTranscodeLink($id, $quality, $codec));
		return self::streamPassthru($mime, $stream);
	}

	// Output a stream directly to the page (used to proxy streams from WaveBox, hiding session key)
	public function outputStream($id)
	{
		// Fetch MIME type and stream
		list($mime, $stream) = $this->fetchBinaryStream($this->getStreamLink($id));
		return self::streamPassthru($mime, $stream);
	}
}
