<?php

class Genre extends AbstractItem
{
	// WaveBox Genre fields
	public $genreId;
	public $genreName;

	// API fields
	public $folders = array();
	public $artists = array();
	public $albums = array();
	public $songs = array();

	// Run constructor and create appropriate API client
	public function __construct(AbstractApiClient $apiClient, $clientType = "GenresApiClient")
	{
		parent::__construct($apiClient, $clientType);
	}

	// Shortcut to return or lazy-load all folders belonging to this genre
	public function fetchFolders()
	{
		return $this->fetchType("folders");
	}

	// Shortcut to return or lazy-load all artists belonging to this genre
	public function fetchArtists()
	{
		return $this->fetchType("artists");
	}

	// Shortcut to return or lazy-load all albums belonging to this genre
	public function fetchAlbums()
	{
		return $this->fetchType("albums");
	}

	// Shortcut to return or lazy-load all songs belonging to this genre
	public function fetchSongs()
	{
		return $this->fetchType("songs");
	}

	// Helper function to fetch and lazy-load objects pertaining to this Genre by their type
	private function fetchType($type)
	{
		// Return type if available
		if (!empty($this->$type))
		{
			return $this->$type;
		}

		// Fetch list of types belonging to this genre
		$this->$type = $this->apiClient->getGenre($this->genreId, $type)->$type;
		return $this->$type;
	}
}
