<?php

// The abstract model from which all others derive
class AbstractModel
{
	// php-wavebox API client
	protected $apiClient;

	// Require API client in constructor
	public function __construct(AbstractApiClient $apiClient, $clientType = null)
	{
		// Override client type if needed
		if (isset($clientType) && is_subclass_of($clientType, "AbstractApiClient"))
		{
			// Copy all properties into a subclassed API client
			$apiClient = $apiClient->copyTo($clientType);
		}

		$this->apiClient = $apiClient;
	}
}
