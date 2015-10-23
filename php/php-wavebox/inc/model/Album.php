<?php

class Album extends AbstractItem
{
	// WaveBox Album fields
	public $artistId;
	public $artistName;
	public $albumArtistId;
	public $albumArtistName;
	public $albumId;
	public $albumName;
	public $releaseYear;

	// API fields
	public $songs = array();

	// Run constructor and create appropriate API client
	public function __construct(AbstractApiClient $apiClient, $clientType = "AlbumsApiClient")
	{
		parent::__construct($apiClient, $clientType);
	}

	// Shortcut to return or lazy-load all songs belonging to this album
	public function fetchSongs()
	{
		// Return songs if available
		if (!empty($this->songs))
		{
			return $this->songs;
		}

		// Fetch list of songs belonging to this album
		$this->songs = $this->apiClient->getAlbum($this->albumId, true)->songs;
		return $this->songs;
	}
}
