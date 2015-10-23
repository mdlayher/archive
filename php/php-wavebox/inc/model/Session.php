<?php

class Session extends AbstractModel
{
	// WaveBox Session fields
	public $rowId;
	public $userId;
	public $clientName;
	public $createTime;
	public $updateTime;

	// Print string for Session
	public function __toString()
	{
		return sprintf("[Session: userId=%s, userName=%s]", $this->userId, $this->userName);
	}
}
