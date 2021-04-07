<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\commands\EnderInvSeeCommand;
use BlockHorizons\InvSee\commands\InvSeeCommand;
use BlockHorizons\InvSee\player\InvSeePlayerList;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase{

	private InvSeePlayerList $player_list;

	protected function onLoad() : void{
		$this->player_list = new InvSeePlayerList();
	}

	protected function onEnable() : void{
		$this->initVirions();
		$this->player_list->init($this);
		$this->getServer()->getCommandMap()->registerAll($this->getName(), [
			new EnderInvSeeCommand($this, "enderinvsee"),
			new InvSeeCommand($this, "invsee")
		]);
	}

	protected function onDisable() : void{
		$this->player_list->close();
	}

	private function initVirions() : void{
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}
	}

	public function getPlayerList() : InvSeePlayerList{
		return $this->player_list;
	}
}