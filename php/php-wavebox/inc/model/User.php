<?php

class User extends AbstractModel
{
	// WaveBox User fields
	public $userId;
	public $userName;
	public $password;
	public $role;
	public $currentSession;
	public $sessions = array();
	public $lastfmSession;
	public $createTime;
	public $deleteTime;

	// Constants for user roles
	const ROLE_TEST = 1;
	const ROLE_GUEST = 2;
	const ROLE_USER = 3;
	const ROLE_ADMIN = 4;

	// WaveBox role enumerations to string
	protected static $roles = array(
		self::ROLE_TEST => "Test",
		self::ROLE_GUEST => "Guest",
		self::ROLE_USER => "User",
		self::ROLE_ADMIN => "Admin",
	);

	// Run constructor and create appropriate API client
	public function __construct(AbstractApiClient $apiClient, $clientType = "UsersApiClient")
	{
		parent::__construct($apiClient, $clientType);
	}

	// Print string for User
	public function __toString()
	{
		return sprintf("[User: userId=%s, userName=%s]", $this->userId, $this->userName);
	}

	// Return a string indicating the role of this user
	public function getRole()
	{
		if (isset(self::$roles[$this->role]))
		{
			return self::$roles[$this->role];
		}

		return null;
	}

	// Shortcut to delete this user from the WaveBox server
	public function delete()
	{
		return $this->apiClient->deleteUser($this->userId);
	}
}
