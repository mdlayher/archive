<?php

abstract class AbstractItem extends AbstractModel
{
	// WaveBox Item fields
	public $itemTypeId;
	public $itemId;
	public $artId;

	// Constants for item types
	const ITEMTYPE_ARTIST = 1;
	const ITEMTYPE_ALBUM = 2;
	const ITEMTYPE_SONG = 3;
	const ITEMTYPE_FOLDER = 4;
	const ITEMTYPE_VIDEO = 10;
	const ITEMTYPE_GENRE = 14;
	const ITEMTYPE_ALBUMARTIST = 15;
	const ITEMTYPE_UNKNOWN = 2147483647;

	// WaveBox item type enumerations to string
	protected static $itemTypes = array(
		self::ITEMTYPE_ARTIST => "Artist",
		self::ITEMTYPE_ALBUM => "Album",
		self::ITEMTYPE_SONG  => "Song",
		self::ITEMTYPE_FOLDER => "Folder",
		self::ITEMTYPE_VIDEO => "Video",
		self::ITEMTYPE_GENRE => "Genre",
		self::ITEMTYPE_ALBUMARTIST => "AlbumArtist",
		self::ITEMTYPE_UNKNOWN => "Unknown",
	);

	// Print string for Item
	public function __toString()
	{
		return sprintf("[%s: itemId=%s, artId=%s]", $this->getItemType(), $this->itemId, $this->artId);
	}

	// Return a string indicating the item type of this item, if available
	public function getItemType()
	{
		// Return string
		if (isset(self::$itemTypes[$this->itemTypeId]))
		{
			return self::$itemTypes[$this->itemTypeId];
		}

		// Return unknown
		return self::$itemTypes[self::ITEMTYPE_UNKNOWN];
	}

	// Returns indicating if item is a media item or not
	public function isMedia()
	{
		if ($this->itemTypeId == self::ITEMTYPE_SONG || $this->itemTypeId == self::ITEMTYPE_VIDEO)
		{
			return true;
		}

		return false;
	}

	// Shortcut to get the art link for this item
	public function getArtLink($size = null)
	{
		// Return direct stream link
		return $this->apiClient->getArtLink($this->artId, $size);
	}

	// Shortcut to output the art stream for this item
	public function outputArtStream($size = null)
	{
		return $this->apiClient->outputArtStream($this->artId, $size);
	}
}
