<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

use pocketmine\Server;

final class PrefixOnlinePlayerSelector implements PlayerSelector{

	public function __construct(
		private Server $server
	){}

	public function select(string $input) : string{
		return $this->server->getPlayerByPrefix($input)?->getName() ?? $input;
	}
}