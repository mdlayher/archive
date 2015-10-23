<?php

// The abstract API client from which all ApiClients which use models which extend AbstractItem derive
class AbstractItemApiClient extends AbstractApiClient
{
	// Get a URL path for art stream
	public function getArtLink($id, $size = null)
	{
		// Build URL path
		$url = sprintf("http://%s:%s/api/art/%s?s=%s", $this->host, $this->port, $id, $this->session);

		// Add size if available
		if (isset($size) && is_int($size))
		{
			$url .= "&size=" . $size;
		}

		return $url;
	}

	// Output an art stream directly to the page (used to proxy images from WaveBox, hiding session key)
	public function outputArtStream($id, $size = null)
	{
		// Fetch MIME type and stream
		list($mime, $stream) = $this->fetchBinaryStream($this->getArtLink($id, $size));
		return self::streamPassthru($mime, $stream);
	}
}
