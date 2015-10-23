<?php

// The abstract API client from which all ApiClients which use artist models which extend AbstractItem derive
class AbstractArtistsApiClient extends AbstractItemApiClient
{
	// Get an artist object by its ID, add albums, and songs if requested
	protected function getArtistObject($id, $getAlbums, $getSongs, $getCounts, $apiPath, $object)
	{
		// Convert booleans to strings
		$getSongs = $getSongs ? "true" : "false";

		// Send API request for a single artist by ID
		$response = $this->fetchJson(sprintf("%s/%s?includeSongs=%s", $apiPath, $id, $getSongs));

		// Determine which key to grab, depending on API call
		$key = "artists";
		if ($apiPath == "albumartists")
		{
			$key = "albumArtists";
		}

		// Populate Artist/AlbumArtist object using API response
		$artist = self::fieldsToSingle($response[$key][0], $object);

		// Add albums if available
		if ($getAlbums && isset($response["albums"]))
		{
			$artist->albums = self::fieldsToList($response["albums"], new Album($this));
		}

		// Add songs if available
		if ($getSongs && isset($response["songs"]))
		{
			$artist->songs = self::fieldsToList($response["songs"], new Song($this));
		}

		// Add counts if available
		if ($getCounts && isset($response["counts"]))
		{
			$artist->counts = $response["counts"];
		}

		// Return object
		return $artist;
	}
}
