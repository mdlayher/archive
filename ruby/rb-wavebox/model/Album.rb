class Album
	attr_accessor :item_type_id, :album_artist_id, :album_artist_name, :album_id, :album_name, :release_year, :musicbrainz_id, :art_id

	def initialize(api, album, songs = nil)
		# API client
		@api = api

		# Album fields
		@item_type_id = album['itemTypeId'].to_i()
		@album_artist_id = album['albumArtistId'].to_i()
		@album_artist_name = album['albumArtistName']
		@album_id = album['albumId'].to_i()
		@album_name = album['albumName']
		@release_year = album['releaseYear'].to_i()
		@musicbrainz_id = album['musicBrainzId']
		@art_id = album['artId'].to_i()

		# Song fields
		unless songs.nil?
			@songs = songs.map { |s| Song.new(s) }
		end
	end

	# Fetch all songs belonging to this album
	def fetch_songs()
		# Return list of songs if already stored
		unless @songs.nil?
			return @songs
		end

		# Fetch and retrieve list of songs
		@songs = @api.fetch_json("albums/" + @album_id.to_s())['songs'].map { |s| Song.new(s) }
	end
end
