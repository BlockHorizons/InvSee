<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\commands\EnderInvSeeCommandExecutor;
use BlockHorizons\InvSee\commands\InvSeeCommandExecutor;
use BlockHorizons\InvSee\commands\InvSeeModuleCommandExecutor;
use BlockHorizons\InvSee\module\ModuleManager;
use BlockHorizons\InvSee\player\InvSeePlayerList;
use BlockHorizons\InvSee\utils\playerselector\ExactPlayerSelector;
use BlockHorizons\InvSee\utils\playerselector\PrefixOfflinePlayerSelector;
use BlockHorizons\InvSee\utils\playerselector\PrefixOnlinePlayerSelector;
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

		$command_player_selector = match($this->getConfig()->get("command-player-selector-type")){
			"exact" => new ExactPlayerSelector(),
			"prefix-online" => new PrefixOnlinePlayerSelector($this->getServer()),
			"prefix-offline" => new PrefixOfflinePlayerSelector($this->getServer()),
			default => throw new RuntimeException("Invalid command-player-selector-type configured: {$this->getConfig()->get("command-player-selector-type")}")
		};

		$command = $this->getCommand("invsee");
		if(!($command instanceof PluginCommand)){
			throw new RuntimeException("Command \"invsee\" is not registered");
		}
		$command->setExecutor(new InvSeeCommandExecutor($this->player_list, $command_player_selector));

		$command = $this->getCommand("enderinvsee");
		if(!($command instanceof PluginCommand)){
			throw new RuntimeException("Command \"enderinvsee\" is not registered");
		}
		$command->setExecutor(new EnderInvSeeCommandExecutor($this->player_list, $command_player_selector));

		$this->module_manager = new ModuleManager($this);
		$this->module_manager->init();

		$command = $this->getCommand("invseemodule");
		if(!($command instanceof PluginCommand)){
			throw new RuntimeException("Command \"invseemodule\" is not registered");
		}
		$command->setExecutor(new InvSeeModuleCommandExecutor($this->module_manager));
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