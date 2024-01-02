<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

use pocketmine\Server;

/**
 * PlayerSelector implementation that selects online player by their name prefix, or falls back to input.
 */
final class PrefixOnlinePlayerSelector implements PlayerSelector{

	public function __construct(
		readonly private Server $server
	){}

	public function select(string $input) : string{
		return $this->server->getPlayerByPrefix($input)?->getName() ?? $input;
	}
}