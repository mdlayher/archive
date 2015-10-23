<?php

// API client for /api/albumartists
class AlbumArtistsApiClient extends AbstractArtistsApiClient
{
	// Get an album artist object by its ID
	public function getAlbumArtist($id, $getAlbums = true, $getSongs = false, $getCounts = false)
	{
		return $this->getArtistObject($id, $getAlbums, $getSongs, $getCounts, "albumartists", new AlbumArtist($this));
	}

	// Fetch a full list of album artist objects
	public function fetchAlbumArtists()
	{
		return $this->fetchList("albumartists", new AlbumArtist($this));
	}
}
