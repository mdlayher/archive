require 'json'
require 'net/http'

require_relative "model/Album.rb"
require_relative "model/Artist.rb"
require_relative "model/Song.rb"

class WaveBoxClient
	# Initializer, set host and port
	def initialize(host, port = 6500)
		@host = host
		@port = port
	end

	# Authenticate to WaveBox server
	def login(username, password, client = "rb-wavebox")
		# Perform login request, return false on failure
		begin
			res = fetch_json(sprintf("login?u=%s&p=%s&c=%s", username, password, client))
		rescue
			return false
		end

		# Store session key for later use
		@session = res['sessionId']
		true
	end

	# Log out and delete this session from WaveBox server
	def logout()
		# Perform logout request, return false on failure
		begin
			fetch_json("logout")
		rescue
			return false
		end

		# Success
		true
	end

	# Authenticate using a pre-existing session key
	def use_session(session)
		# Perform login request with session
		begin
			res = fetch_json("login?s=" + session)
		rescue
			return false
		end

		# Server should return matching session ID
		unless session == res['sessionId']
			return false
		end

		# Store session key for later use
		@session = session
		true
	end

	# Retrieve server status
	def get_status(extended = false)
		fetch_json("status?extended=" + extended.to_s())['status']
	end

	# Generate and return a single Artist object
	def get_artist(id, get_songs = false)
		res = fetch_json(sprintf("artists/%d?includeSongs=%s", id, get_songs.to_s()))
		Artist.new(self, res['artists'][0], res['albums'], res['songs'])
	end

	# Generate and return a list of all Artist objects
	def fetch_artists()
		fetch_json("artists")['artists'].map { |a| Artist.new(self, a) }
	end

	# Generate and return a single Album object
	def get_album(id)
		res = fetch_json("albums/" + id.to_s())
		Album.new(self, res['albums'][0], res['songs'])
	end

	# Generate and return a list of all Album objects
	def fetch_albums()
		fetch_json("albums")['albums'].map { |a| Album.new(self, a) }
	end

	# Generate and return a single Song object
	def get_song(id)
		res = fetch_json("song/" + id.to_s())
		Song.new(res['songs'][0])
	end

	# Generate and return a list of all Song objects
	def fetch_songs()
		fetch_json("songs")['songs'].map { |s| Song.new(s) }
	end

	# Perform an API request using relative API path, return the output JSON converted to hash
	def fetch_json(path)
		# Generate and parse URI
		uri = URI.parse(sprintf("http://%s:%d/api/%s", @host, @port, path))

		# Start HTTP request block
		res = Net::HTTP.start(uri.host, uri.port) do |http|
			# Generate HTTP request
			req = Net::HTTP::Get.new(uri.request_uri)

			# Set request parameters
			req['Accept'] = "application/json"
			req['Accept-Encoding'] = "gzip"

			# Set session cookie if available
			unless @session.nil?
				req['Cookie'] = "wavebox_session=" + @session
			end

			# Perform and return request
			http.request(req)
		end

		# Verify HTTP 200 success
		unless res.code == 200.to_s()
			raise "Error: HTTP " + res.code
		end

		# Deal with any content encodings
		out = case res['Content-Encoding']
			# GZIP
			when "gzip" then JSON.parse(Zlib::GzipReader.new(StringIO.new(res.body.to_s)).read())
			# Uncompressed + UTF8 BOM
			else JSON.parse(res.body[3, res.body.length])
		end

		# Check for errors in JSON response
		unless out['error'].nil?
			raise "Error: " + out['error']
		end

		# Return
		out
	end
end
