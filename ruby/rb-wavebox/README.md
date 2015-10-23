rb-wavebox
==========

Ruby class for interacting with a WaveBox server (https://github.com/einsteinx2/WaveBox).  MIT Licensed.

Example
-------

This script shows a basic demonstration of connecting to a WaveBox server, printing its status, and fetching
some basic information about an artist, their associated albums, and their associated songs.

```ruby
# rb-wavebox - Example basic usage script
require_relative "rb-wavebox.rb"

# Connect a local WaveBox server
api = WaveBoxClient.new("localhost", 6500)

# Try session authentication
if !api.use_session("ABCDEF0123456789")
	# On failed authentication, try username/password
	if !api.login("test", "test")
		puts "example: authentication failed"
		exit
	end
end

# Fetch extended status and output some metrics
stat = api.get_status(true)

printf("%s - WaveBox %s, %s\n", stat['hostname'], stat['version'], stat['buildDate'])
puts "\t- artists: " + stat['artistCount'].to_s()
puts "\t-  albums: " + stat['albumCount'].to_s()
puts "\t-   songs: " + stat['songCount'].to_s()

# localhost - WaveBox 0.9.0.0 - November 08, 2013
#     - artists: 218
#     -  albums: 420
#     -   songs: 4614

# Fetch an artist, print title
artist = api.get_artist(6086)
printf("%s [MB: %s]\n", artist.artist_name, artist.musicbrainz_id)

# Fetch all albums
artist.fetch_albums().each { |album|
	# Print release year and album title
	printf("\t- %d - %s\n", album.release_year, album.album_name)

	# Fetch all songs
	album.fetch_songs().each { |song|
		# Print track number and song title
		printf("\t\t- %02d - %s\n", song.track_number, song.song_name)
	}
}

# Walk the Moon - [MB: d4aad415-9cd0-4845-9b05-0416fdcc9fc4]
#     - 2012 - Walk the Moon
#         - 01 - Quesadilla
#         - 02 - Lisa Baby
#         - 03 - Next in Line
#         - 04 - Anna Sun
#         - 05 - Tightrope
#         - 06 - Jenny
#         - 07 - Shiver Shiver
#         - 08 - Lions
#         - 09 - Iscariot
#         - 10 - Fixin'
#         - 11 - I Can Lift a Car

# Log out, destroy session
api.logout()
```
