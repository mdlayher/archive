php-wavebox
===========

PHP 5.4+ class for interacting with a WaveBox server (https://github.com/einsteinx2/WaveBox).  MIT Licensed.

Example
-------

Once a connection is made to a WaveBox server, it is trivial to pull a variety of information from the WaveBox API.

This demo shows the following:

1. Establishing a connection to a WaveBox server
2. Authentication via session key, or fallback to username/password
3. Retrieving an Artist object
4. Retrieving Album objects associated with an Artist
5. Retrieving Song objects associated with an Album
6. Outputting links to play media by proxying the streams through php-wavebox

```php
<?php
// php-wavebox - Example basic usage script
require_once __DIR__ . "/php-wavebox.php";

// Connect to a local WaveBox server, use the Artists API
$api = new WaveBoxApiClient("localhost", 6500);

// Try session authentication
if (!$api->useSession("ABCDEF0123456789"))
{
	// On failed authentication, try username/password
	if (!$api->login("test", "test"))
	{
		printf("example: authentication failed\n");
		exit;
	}
}

// Output file streams for art and transcode streams, proxying through
// php-wavebox so we don't reveal the session key to clients!

// If requested, output art stream for item
if (isset($_GET['art']))
{
	$api->outputArtStream((int)$_GET['art'], 200);
	$api->logout();
	exit;
}

// If requested, output transcode stream for song
if (isset($_GET['song']))
{
	$song = $api->getSong((int)$_GET['song']);
	$song->outputTranscodeStream();
	$api->logout();
	exit;
}

printf("<pre>\n");

// Grab an artist object
$artist = $api->getArtist(2385);
printf("Artist: %s\n", $artist->artistName);

// Print artist's albums
foreach ($artist->fetchAlbums() as $album)
{
	// Output art and album info
	printf("<img src=\"?art=%d\" />\n", $album->artId);
	printf("  - %d - %s\n", $album->releaseYear, $album->albumName);

	// Print album's songs, and provide a "play" link to play a transcoded stream
	foreach ($album->fetchSongs() as $song)
	{
		printf("    - [<a href=\"?song=%d\">&gt;</a>] %d - %s\n",
			$song->itemId, $song->trackNumber, $song->songName);
	}
}

// Destroy this session
$api->logout();

printf("</pre>\n");

/* Example output:

Artist: Gotye
  [art]
  - 2011 - Making Mirrors
      - [>] 1 - Making Mirrors
      - [>] 2 - Easy Way Out
      - [>] 3 - Somebody That I Used to Know
      - [>] 4 - Eyes Wide Open
      - [>] 5 - Smoke and Mirrors
      - [>] 6 - I Feel Better
      - [>] 7 - In Your Light
      - [>] 8 - State of the Art
      - [>] 9 - Don't Worry, We'll Be Watching You
      - [>] 10 - Giving Me a Chance
      - [>] 11 - Save Me
      - [>] 12 - Bronte

*/
```
