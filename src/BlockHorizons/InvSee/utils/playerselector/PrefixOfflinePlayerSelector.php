<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\playerselector;

use DirectoryIterator;
use pocketmine\Server;
use SplFileInfo;
use Symfony\Component\Filesystem\Path;
use function stripos;
use function strlen;
use function strtolower;
use const PHP_INT_MAX;

/**
 * PlayerSelector implementation that selects offline player by their name prefix, or falls back to input. This selector
 * goes through file names in /players/ directory.
 */
final class PrefixOfflinePlayerSelector implements PlayerSelector{

	readonly private string $path;

	public function __construct(Server $server){
		$this->path = Path::join($server->getDataPath(), "players");
	}

	public function select(string $input) : string{
		$found = $input;
		$name = strtolower($input);
		$delta = PHP_INT_MAX;
		/** @var SplFileInfo $entry */
		foreach(new DirectoryIterator($this->path) as $entry){
			if($entry->isFile() && $entry->getExtension() === "dat" && stripos($entry_player_name = $entry->getBasename("." . $entry->getExtension()), $name) === 0){
				$cur_delta = strlen($entry_player_name) - strlen($name);
				if($cur_delta < $delta){
					$found = $entry_player_name;
					$delta = $cur_delta;
				}
				if($cur_delta === 0){
					break;
				}
			}
		}
		return $found;
	}
}