<?php

// API client for /api/users
class UsersApiClient extends AbstractApiClient
{
	// Get a user object by its ID
	public function getUser($id)
	{
		return $this->generateSingleUser(sprintf("users/%d", $id));
	}

	// Get a user object by its username
	public function getUserByName($username)
	{
		// Grab all users, try to find the one that matches this user
		$users = $this->fetchUsers();
		foreach ($users as $u)
		{
			if ($u->userName == $username)
			{
				return $u;
			}
		}

		return null;
	}

	// Special: get the current php-wavebox user's object
	public function getCurrentUser()
	{
		return $this->getUserByName($this->username);
	}

	// Fetch a full list of user objects
	public function fetchUsers()
	{
		$users = $this->fetchList("users", new User($this));

		// Convert sessions arrays into objects
		foreach ($users as $u)
		{
			if (isset($u->currentSession))
			{
				$u->currentSession = self::fieldsToSingle($u->currentSession, new Session($this));
			}
			$u->sessions = self::fieldsToList($u->sessions, new Session($this));
		}

		return $users;
	}

	// Create a user on the WaveBox server, optionally specifying their role
	public function createUser($username, $password, $role = User::ROLE_USER)
	{
		// Send API request to create a user
		return $this->generateSingleUser(sprintf("?action=create&username=%s&password=%s&role=%d", $username, $password, $role));
	}

	// Delete a user on the WaveBox server
	public function deleteUser($id)
	{
		return $this->generateSingleUser(sprintf("%d?action=delete", $id));
	}

	// Method to fetch a user from the users API, perform needed operations, and setup sub-objects
	private function generateSingleUser($api)
	{
		// Send API request
		$response = $this->fetchJson("users/" . $api);

		// Populate User object using API response
		$user = self::fieldsToSingle($response["users"][0], new User($this));

		// Generate current session object
		if (isset($response["users"][0]["currentSession"]))
		{
			$user->currentSession = self::fieldsToSingle($response["users"][0]["currentSession"], new Session($this));
		}

		// Generate session objects lists
		if (isset($response["users"][0]["sessions"]))
		{
			$user->sessions = self::fieldsToList($response["users"][0]["sessions"], new Session($this));
		}

		// Return object
		return $user;
	}
}
