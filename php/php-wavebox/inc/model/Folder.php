<?php

class Folder extends AbstractItem
{
	// WaveBox Folder fields
	public $folderId;
	public $folderName;
	public $parentFolderId;
	public $mediaFolderId;
	public $folderPath;

	// API fields
	public $subfolders = array();
	public $songs = array();
	public $videos = array();

	// Run constructor and create appropriate API client
	public function __construct(AbstractApiClient $apiClient, $clientType = "FoldersApiClient")
	{
		parent::__construct($apiClient, $clientType);
	}

	// Shortcut to return or lazy-load all subfolders beneath this folder
	public function fetchSubfolders()
	{
		// Return subfolders if available
		if (!empty($this->subfolders))
		{
			return $this->subfolders;
		}

		// Fetch list of subfolders belonging to this album
		$this->subfolders = $this->apiClient->getFolder($this->folderId, true)->subfolders;
		return $this->subfolders;
	}

	// Shortcut to return or lazy-load all songs belonging to this folder
	public function fetchSongs()
	{
		// Return songs if available
		if (!empty($this->songs))
		{
			return $this->songs;
		}

		// Fetch list of songs belonging to this album
		$this->songs = $this->apiClient->getFolder($this->folderId, true, true)->songs;
		return $this->songs;
	}

	// Shortcut to return or lazy-load all videos belonging to this folder
	public function fetchVideos()
	{
		// Return videos if available
		if (!empty($this->videos))
		{
			return $this->videos;
		}

		// Fetch list of videos belonging to this album
		$this->videos = $this->apiClient->getFolder($this->folderId, true, true)->videos;
		return $this->videos;
	}
}
