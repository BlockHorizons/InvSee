<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;

class InventoryHandler{

	/** @var InvSeePlayer[] */
	protected $players = [];

	/** @var TaskScheduler */
	protected $scheduler;

	public function __construct(Loader $loader){
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($loader);
		}

		$this->scheduler = $loader->getScheduler();
	}

	public function get(string $player) : InvSeePlayer{
		return $this->players[strtolower($player)] ??= new InvSeePlayer($this, $player);
	}

	public function onJoin(Player $player) : void{
		if(isset($this->players[$name = strtolower($player->getName())])){
			$this->players[$name]->onJoin($player);
		}
	}

	public function onQuit(Player $player) : void{
		if(isset($this->players[$name = strtolower($player->getName())])){
			$this->players[$name]->onQuit($player);
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
					$this->players[$name]->destroy();
					unset($this->players[$name]);
				}
			}
		}), $delay);
	}
}