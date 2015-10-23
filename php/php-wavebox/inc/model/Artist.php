<?php

class Artist extends AbstractItem
{
	// WaveBox Artist fields
	public $artistId;
	public $artistName;
	public $musicBrainzId;

	// API fields
	public $albums = array();
	public $songs = array();
	public $counts = array();

	// Run constructor and create appropriate API client
	public function __construct(AbstractApiClient $apiClient, $clientType = "ArtistsApiClient")
	{
		parent::__construct($apiClient, $clientType);
	}

	// Shortcut to return or lazy-load all albums belonging to this artist
	public function fetchAlbums()
	{
		// Return albums if available
		if (!empty($this->albums))
		{
			return $this->albums;
		}

		// Fetch list of albums belonging to this artist
		$this->albums = $this->apiClient->getArtist($this->artistId, true)->albums;
		return $this->albums;
	}

	// Shortcut to return or lazy-load all songs belonging to this artist
	public function fetchSongs()
	{
		// Return songs if available
		if (!empty($this->songs))
		{
			return $this->songs;
		}

		// Fetch list of songs belonging to this artist
		$this->songs = $this->apiClient->getArtist($this->artistId, false, true)->songs;
		return $this->songs;
	}

	// Shortcut to return or lazy-load all counts belonging to this artist
	public function fetchCounts()
	{
		// Return songs if available
		if (!empty($this->counts))
		{
			return $this->counts;
		}

		// Fetch list of counts belonging to this artist
		$this->counts = $this->apiClient->getAlbumArtist($this->albumArtistId, false, false, true)->counts;
		return $this->counts;
	}
}
