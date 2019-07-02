<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\player\Player;

class InventoryHandler{

	/** @var InvSeePlayer[] */
	protected $players = [];

	public function __construct(Loader $loader){
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($loader);
		}
	}

	public function get(string $player) : InvSeePlayer{
		return $this->players[$player = strtolower($player)] ?? ($this->players[$player] = new InvSeePlayer($this, $player));
	}

	public function onJoin(Player $player) : void{
		if(isset($this->players[$name = $player->getLowerCaseName()])){
			$this->players[$name]->onJoin($player);
		}
	}

	public function onQuit(Player $player) : void{
		if(isset($this->players[$name = $player->getLowerCaseName()])){
			$this->players[$name]->onQuit($player);
		}
	}

	public function tryGarbageCollecting(string $player) : bool{
		if(isset($this->players[$name = strtolower($player)])){
			$player = $this->players[$name];
			if(
				empty($player->getEnderChestInventoryMenu()->getInventory()->getViewers()) &&
				empty($player->getInventoryMenu()->getInventory()->getViewers())
			){
				unset($this->players[$name]);
				var_dump("GARBAGE COLLECTED {$player}");
				return true;
			}
		}

		return false;
	}
}