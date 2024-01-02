<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

/**
 * PlayerSelector implementation that selects player by their exact (full) name.
 */
final class ExactPlayerSelector implements PlayerSelector{

	public function __construct(){
	}

	public function select(string $input) : string{
		return $input;
	}
}