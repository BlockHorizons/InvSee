<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

/**
 * Implements mechanism behind <player> selection. Used by commands like /invsee <player>.
 */
interface PlayerSelector{

	public function select(string $input) : string;
}