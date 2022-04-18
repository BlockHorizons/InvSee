<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

interface PlayerSelector{

	public function select(string $input) : string;
}