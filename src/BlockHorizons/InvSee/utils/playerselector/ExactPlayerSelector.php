<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

final class ExactPlayerSelector implements PlayerSelector{

	public function __construct(){
	}

	public function select(string $input) : string{
		return $input;
	}
}