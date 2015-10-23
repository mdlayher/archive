<?php

// API client for /api/videos
class VideosApiClient extends AbstractMediaItemApiClient
{
	// Get a video object by its ID
	public function getVideo($id)
	{
		return $this->getSingle("videos", $id, new Video($this));
	}

	// Fetch a full list of video objects
	public function fetchVideos()
	{
		return $this->fetchList("videos", new Video($this));
	}

	// Search for videos
	public function searchVideos($query)
	{
		return $this->search($query, "videos")["videos"];
	}
}
