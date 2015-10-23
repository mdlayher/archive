<?php

// API client for /api/folders
class FoldersApiClient extends AbstractItemApiClient
{
	// Get a folder object by its ID, optionally returning subfolders, and media contained
	public function getFolder($id, $getSubfolders = true, $getMedia = false)
	{
		// Convert boolean to string
		$getMedia = $getMedia ? "true" : "false";

		// Send API request for a single folder by ID
		$response = $this->fetchJson(sprintf("folders/%s?recursiveMedia=%s", $id, $getMedia));

		// Populate Folder object using API response
		$folder = self::fieldsToSingle($response["containingFolder"], new Folder($this));

		// Add subfolders if available
		if ($getSubfolders && isset($response["folders"]))
		{
			$folder->subfolders = self::fieldsToList($response["folders"], new Folder($this));
		}

		// Add media if available
		if ($getMedia)
		{
			if (isset($response["songs"]))
			{
				$folder->songs = self::fieldsToList($response["songs"], new Song($this));
			}

			if (isset($response["videos"]))
			{
				$folder->videos = self::fieldsToList($response["videos"], new Video($this));
			}
		}

		// Return object
		return $folder;
	}

	// Fetch a full list of folder objects
	public function fetchFolders()
	{
		return $this->fetchList("folders", new Folder($this));
	}
}
