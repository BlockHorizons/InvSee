<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player;

use BlockHorizons\InvSee\Loader;
use InvalidArgumentException;
use Logger;
use pocketmine\player\Player;
use pocketmine\Server;
use PrefixedLogger;

final class InvSeePlayerList{

	private Server $server;
	private Logger $logger;

	/** @var InvSeePlayer[] */
	private array $players = []; // these are NOT online players, these are players whose inventories are being spied upon

	/** @var string[] */
	private array $joined = [];

	public function __construct(){
	}

	public function init(Loader $loader) : void{
		$this->server = $loader->getServer();
		$this->logger = $loader->getLogger();
		$this->server->getPluginManager()->registerEvents(new InvSeePlayerListEventListener($loader), $loader);
	}

	public function get(string $player) : ?InvSeePlayer{
		return $this->players[strtolower($player)] ?? null;
	}

	public function getOrCreate(string $player) : InvSeePlayer{
		return $this->players[strtolower($player)] ?? $this->create($player);
	}

	private function create(string $player) : InvSeePlayer{
		if(isset($this->players[$name = strtolower($player)])){
			throw new InvalidArgumentException("Attempted to create a duplicate player");
		}

		$this->logger->debug("Creating session: {$name}");

		$instance = new InvSeePlayer($player, new PrefixedLogger($this->logger, $name));
		$instance->init($this);
		$this->players[$name] = $instance;
		return $instance;
	}

	public function getOnlinePlayer(string $name) : ?Player{
		return isset($this->joined[$name = strtolower($name)]) ? $this->server->getPlayerByRawUUID($this->joined[$name]) : null;
	}

	public function onPlayerJoin(Player $player) : void{
		$this->joined[$name = strtolower($player->getName())] = $player->getUniqueId()->getBytes();
		if(isset($this->players[$name])){
			$this->players[$name]->onPlayerOnline($player);
		}
	}

	public function onPlayerQuit(Player $player) : void{
		unset($this->joined[$name = strtolower($player->getName())]);
		if(isset($this->players[$name])){
			$this->players[$name]->onPlayerOffline();
		}
	}

	public function destroy(InvSeePlayer $player) : void{
		if(isset($this->players[$name = strtolower($player->getPlayer())])){
			$this->logger->debug("Destroying session: {$name}");
			unset($this->players[$name]);
			$player->destroy();
		}else{
			$this->logger->debug("Tried to destroy non-existent session: {$name}");
		}
	}

	public function close() : void{
		foreach($this->players as $player){
			$this->destroy($player);
		}
	}

	public function tryGarbageCollecting(string $player_name) : void{
		if(isset($this->players[$name = strtolower($player_name)])){
			$player = $this->players[$name];
			if(
				count($player->getEnderChestInventoryMenu()->getInventory()->getViewers()) === 0 &&
				count($player->getInventoryMenu()->getInventory()->getViewers()) === 0
			){
				$this->destroy($player);
			}
		}
	}
}