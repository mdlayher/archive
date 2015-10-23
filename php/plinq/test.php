<?php
	require_once "plinq.php";

	$p = plinq::range(1,100)->map(function($v)
	{
		return $v * 2;
	})->select();

	print_r($p);
