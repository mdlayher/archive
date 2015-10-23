<?php

// API client for /api/songs
class SongsApiClient extends AbstractMediaItemApiClient
{
	// Get a song object by its ID
	public function getSong($id)
	{
		return $this->getSingle("songs", $id, new Song($this));
	}

	// Fetch a full list of song objects
	public function fetchSongs()
	{
		return $this->fetchList("songs", new Song($this));
	}

	// Search for song objects
	public function searchSongs($query)
	{
		return $this->search($query, "songs")["songs"];
	}
}
