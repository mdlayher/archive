<?php

// API client for /api/artists
class ArtistsApiClient extends AbstractArtistsApiClient
{
	// Get an artist object by its ID
	public function getArtist($id, $getAlbums = true, $getSongs = false, $getCounts = false)
	{
		return $this->getArtistObject($id, $getAlbums, $getSongs, $getCounts, "artists", new Artist($this));
	}

	// Fetch a full list of artist objects
	public function fetchArtists()
	{
		return $this->fetchList("artists", new Artist($this));
	}

	// Search for artist objects
	public function searchArtists($query)
	{
		return $this->search($query, "artists")["artists"];
	}
}
