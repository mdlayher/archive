<?php

// API client for /api/genres
class GenresApiClient extends AbstractItemApiClient
{
	// Get an genre object by its ID, optionally fetching different item types which match that genre
	public function getGenre($id, $type = "artists")
	{
		// Send API request for a single genre by ID
		$response = $this->fetchJson(sprintf("genres/%s?type=%s", $id, $type));

		// Populate Genre object using API response
		$genre = self::fieldsToSingle($response["genres"][0], new Genre($this));

		// Available types for genres
		$types = array(
			"folders" => new Folder($this),
			"artists" => new Artist($this),
			"albums" => new Album($this),
			"songs" => new Song($this),
		);

		// Set genre type object
		$object = $types[$type];

		// Add item types which match genre if available
		if (isset($response[$type]))
		{
			$genre->$type = self::fieldsToList($response[$type], $object);
		}

		// Return object
		return $genre;
	}

	// Fetch a full list of genre objects
	public function fetchGenres()
	{
		return $this->fetchList("genres", new Genre($this));
	}
}
