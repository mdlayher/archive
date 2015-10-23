class Song
	attr_accessor :item_type_id, :artist_id, :artist_name, :album_artist_id, :album_artist_name, :album_id, :album_name, :song_name, :track_number, :disc_number, :release_year, :beats_per_minute, :lyrics, :comment, :item_id, :folder_id, :file_type, :duration, :bitrate, :file_size, :last_modified, :file_name, :genre_id, :genre_name, :art_id

	def initialize(song)
		# Song fields
		@item_type_id = song['itemTypeId'].to_i()
		@artist_id = song['artistId'].to_i()
		@artist_name = song['artist_name']
		@album_artist_id = song['albumArtistId'].to_i()
		@album_artist_name = song['albumArtistName']
		@album_id = song['albumId'].to_i()
		@album_name = song['albumName']
		@song_name = song['songName']
		@track_number = song['trackNumber'].to_i()
		@disc_number = song['discNumber'].to_i()
		@release_year = song['releaseYear'].to_i()
		@beats_per_minute = song['beatsPerMinute'].to_i()
		@lyrics = song['lyrics']
		@comment = song['comment']
		@item_id = song['item_id'].to_i()
		@folder_id = song['folder_id'].to_i()
		@file_type = song['file_type'].to_i()
		@duration = song['duration'].to_i()
		@file_size = song['fileSize'].to_i()
		@last_modified = song['lastModified'].to_i()
		@file_name = song['fileName']
		@genre_id = song['genreId'].to_i()
		@genre_name = song['genreName']
		@art_id = song['artId'].to_i()
	end
end
