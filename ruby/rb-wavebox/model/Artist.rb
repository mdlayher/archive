class Artist
	attr_accessor :item_type_id, :artist_id, :artist_name, :musicbrainz_id

	def initialize(api, artist, albums = nil, songs = nil)
		# API client
		@api = api

		# Artist fields
		@item_type_id = artist['itemTypeId'].to_i()
		@artist_id = artist['artistId'].to_i()
		@artist_name = artist['artistName']
		@musicbrainz_id = artist['musicBrainzId']

		# Album fields
		unless albums.nil?
			@albums = albums.map { |a| Album.new(@api, a) }
		end

		# Song fields
		unless songs.nil?
			@songs = songs.map { |s| Song.new(s) }
		end
	end

	# Fetch all albums belonging to this artist
	def fetch_albums()
		# Return list of albums if already stored
		unless @albums.empty?
			return @albums
		end

		# Fetch and retrieve list of albums
		@albums = @api.fetch_json("artists/" + @artist_id.to_s())['albums'].map { |a| Album.new(@api, a) }
	end

	# Fetch all songs belonging to this artist
	def fetch_songs()
		# Return list of songs if already stored
		unless @songs.empty?
			return @songs
		end

		# Fetch and retrieve list of songs
		@songs = @api.fetch_json(sprintf("artists/%d?includeSongs=true", @artist_id))['songs'].map { |s| Song.new(s) }
	end
end
