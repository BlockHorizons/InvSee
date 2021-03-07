<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player;

use BlockHorizons\InvSee\Loader;
use InvalidStateException;
use Logger;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use PrefixedLogger;

class InvSeePlayerList{

	/** @var Server */
	protected $server;

	/** @var TaskScheduler */
	protected $scheduler;

	/** @var Logger */
	protected $logger;

	/** @var InvSeePlayer[] */
	protected $players = []; // these are NOT online players, these are players whose inventories are being spied upon

	/** @var string[] */
	protected $joined = [];

	public function __construct(){
	}

	public function init(Loader $loader) : void{
		$this->server = $loader->getServer();
		$this->scheduler = $loader->getScheduler();
		$this->logger = $loader->getLogger();
		$loader->getServer()->getPluginManager()->registerEvents(new InvSeePlayerListEventListener($loader), $loader);
	}

	public function get(string $player) : ?InvSeePlayer{
		return $this->players[strtolower($player)] ?? null;
	}

	public function getOrCreate(string $player) : InvSeePlayer{
		return $this->players[strtolower($player)] ?? $this->create($player);
	}

	private function create(string $player) : InvSeePlayer{
		if(isset($this->players[$name = strtolower($player)])){
			throw new InvalidStateException("Attempted to create a duplicate player");
		}

		$this->logger->debug("Creating session: {$name}");

		$instance = new InvSeePlayer($player, new PrefixedLogger($this->logger, $name));
		$instance->init($this);
		$this->players[$name] = $instance;
		return $instance;
	}

	public function getOnlinePlayer(string $name) : ?Player{
		return isset($this->joined[$name = strtolower($name)]) ? Server::getInstance()->getPlayerByRawUUID($this->joined[$name]) : null;
	}

	public function onPlayerJoin(Player $player) : void{
		$this->joined[$name = strtolower($player->getName())] = $player->getUniqueId()->toBinary();
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

	public function tryGarbageCollecting(string $player, int $delay = 1) : void{
		$this->scheduler->scheduleDelayedTask(new ClosureTask(function() use($player) : void{
			if(isset($this->players[$name = strtolower($player)])){
				$player = $this->players[$name];
				if(
					empty($player->getEnderChestInventoryMenu()->getInventory()->getViewers()) &&
					empty($player->getInventoryMenu()->getInventory()->getViewers())
				){
					$this->destroy($player);
				}
			}
		}), $delay);
	}
}