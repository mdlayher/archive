<?php

abstract class AbstractMediaItem extends AbstractItem
{
	// WaveBox MediaItem fields
	public $folderId;
	public $fileType;
	public $duration;
	public $bitrate;
	public $fileSize;
	public $lastModified;
	public $fileName;
	public $genreId;
	public $genreName;

	// Constants for item types
	const FILETYPE_AAC = 1;
	const FILETYPE_MP3 = 2;
	const FILETYPE_MPC = 3;
	const FILETYPE_OGG = 4;
	const FILETYPE_WMA = 5;
	const FILETYPE_ALAC = 6;
	const FILETYPE_APE = 7;
	const FILETYPE_FLAC = 8;
	const FILETYPE_WV = 9;
	const FILETYPE_MP4 = 10;
	const FILETYPE_MKV = 11;
	const FILETYPE_AVI = 12;
	const FILETYPE_UNKNOWN = 2147483647;

	// WaveBox file type enumerations to string
	protected static $fileTypes = array(
		self::FILETYPE_AAC => "AAC",
		self::FILETYPE_MP3 => "MP3",
		self::FILETYPE_MPC => "MPC",
		self::FILETYPE_OGG => "OGG",
		self::FILETYPE_WMA => "WMA",
		self::FILETYPE_ALAC => "ALAC",
		self::FILETYPE_APE => "APE",
		self::FILETYPE_FLAC => "FLAC",
		self::FILETYPE_WV => "WV",
		self::FILETYPE_MP4 => "MP4",
		self::FILETYPE_MKV => "MKV",
		self::FILETYPE_AVI => "AVI",
		self::FILETYPE_UNKNOWN => "Unknown",
	);

	// Print string for MediaItem
	public function __toString()
	{
		return sprintf("[%s: itemId=%s, fileType=%s, fileName=%s]", $this->getItemType(), $this->itemId, $this->getFileType(), $this->fileName);
	}

	// Return a string indicating the file type of this media item, if available
	public function getFileType()
	{
		// Return string
		if (isset(self::$fileTypes[$this->fileType]))
		{
			return self::$fileTypes[$this->fileType];
		}

		// Return unknown
		return self::$itemTypes[self::FILETYPE_UNKNOWN];
	}

	// Returns indicating if media item is a song or not
	public function isSong()
	{
		if ($this->getItemType() == self::ITEMTYPE_SONG)
		{
			return true;
		}

		return false;
	}

	// Returns indicating if media item is a video or not
	public function isVideo()
	{
		if ($this->getItemType() == self::ITEMTYPE_VIDEO)
		{
			return true;
		}

		return false;
	}

	// Shortcut to get the stream link for this media item
	public function getStreamLink()
	{
		return $this->apiClient->getStreamLink($this->itemId);
	}

	// Shortcut to get the transcode link for this media item
	public function getTranscodeLink($quality = "medium", $codec = null)
	{
		return $this->apiClient->getTranscodeLink($this->itemId, $quality, $codec);
	}

	// Shortcut to output the stream for this media item
	public function outputStream()
	{
		return $this->apiClient->outputStream($this->itemId);
	}

	// Shortcut to output the transcode stream for this media item
	public function outputTranscodeStream($quality = "medium", $codec = null)
	{
		return $this->apiClient->outputTranscodeStream($this->itemId, $quality, $codec);
	}
}
