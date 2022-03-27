<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\commands\EnderInvSeeCommandExecutor;
use BlockHorizons\InvSee\commands\InvSeeCommandExecutor;
use BlockHorizons\InvSee\module\ModuleManager;
use BlockHorizons\InvSee\player\InvSeePlayerList;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;
use RuntimeException;

final class Loader extends PluginBase{

	private InvSeePlayerList $player_list;
	private ModuleManager $module_manager;

	protected function onLoad() : void{
		$this->player_list = new InvSeePlayerList();
	}

	protected function onEnable() : void{
		$this->initVirions();
		$this->player_list->init($this);

		$command_map = $this->getServer()->getCommandMap();

		$command = $command_map->getCommand("invsee");
		if(!($command instanceof PluginCommand)){
			throw new RuntimeException("Command \"invsee\" is not registered");
		}
		$command->setExecutor(new InvSeeCommandExecutor($this->player_list));

		$command = $command_map->getCommand("enderinvsee");
		if(!($command instanceof PluginCommand)){
			throw new RuntimeException("Command \"enderinvsee\" is not registered");
		}
		$command->setExecutor(new EnderInvSeeCommandExecutor($this->player_list));

		$this->module_manager = new ModuleManager($this);
		$this->module_manager->init();
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

	public function getModuleManager() : ModuleManager{
		return $this->module_manager;
	}
}