<?php

// API client for /api/albums
class AlbumsApiClient extends AbstractItemApiClient
{
	// Get an album object by its ID, optionally adding songs
	public function getAlbum($id, $getSongs = true)
	{
		// Send API request for a single album by ID
		$response = $this->fetchJson(sprintf("albums/%s", $id));

		// Populate Album object using API response
		$album = self::fieldsToSingle($response["albums"][0], new Album($this));

		// Add songs if available
		if ($getSongs && isset($response["songs"]))
		{
			$album->songs = self::fieldsToList($response["songs"], new Song($this));
		}

		// Return object
		return $album;
	}

	// Fetch a full list of album objects
	public function fetchAlbums()
	{
		return $this->fetchList("albums", new Album($this));
	}

	// Search for album objects
	public function searchAlbums($query)
	{
		return $this->search($query, "albums")["albums"];
	}
}
