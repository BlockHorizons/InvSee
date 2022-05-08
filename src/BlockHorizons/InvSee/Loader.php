<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\commands\EnderInvSeeCommandExecutor;
use BlockHorizons\InvSee\commands\InvSeeCommandExecutor;
use BlockHorizons\InvSee\commands\InvSeeModuleCommandExecutor;
use BlockHorizons\InvSee\module\ModuleManager;
use BlockHorizons\InvSee\player\InvSeePlayerList;
use BlockHorizons\InvSee\utils\config\Configuration;
use BlockHorizons\InvSee\utils\config\ConfigurationException;
use BlockHorizons\InvSee\utils\playerselector\ExactPlayerSelector;
use BlockHorizons\InvSee\utils\playerselector\PlayerSelector;
use BlockHorizons\InvSee\utils\playerselector\PrefixOfflinePlayerSelector;
use BlockHorizons\InvSee\utils\playerselector\PrefixOnlinePlayerSelector;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\PluginBase;
use RuntimeException;
use function assert;

final class Loader extends PluginBase{

	private InvSeePlayerList $player_list;
	private ModuleManager $module_manager;

	protected function onLoad() : void{
		$this->player_list = new InvSeePlayerList();
	}

	protected function onEnable() : void{
		$this->initVirions();
		try{
			$this->player_list->init($this);

			$config = Configuration::fromConfig($this->getConfig());
			$command_player_selector = match ($config["command-player-selector-type"]) {
				"exact" => new ExactPlayerSelector(),
				"prefix-online" => new PrefixOnlinePlayerSelector($this->getServer()),
				"prefix-offline" => new PrefixOfflinePlayerSelector($this->getServer()),
				default => $config->throwUndefinedConfiguration("command-player-selector-type", "Value of the property must be one of: exact, prefix-online, prefix-offline")
			};
			assert($command_player_selector instanceof PlayerSelector);

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
		}catch(ConfigurationException $e){
			$this->onConfigurationException($e);
		}
	}

	protected function onDisable() : void{
		$this->player_list->close();
	}

	public function onConfigurationException(ConfigurationException $exception) : void{
		$this->getLogger()->error("Configuration property \"{$exception->getOffset()}\" is undefined in {$exception->getFileName()}");
		if($exception->getMessage() !== ""){
			$this->getLogger()->warning($exception->getMessage());
		}
		$this->getLogger()->warning("Define the configuration property to fix this error.");
		$this->getLogger()->warning("Alternatively, delete the configuration file so that a new configuration file is generated with the property defined.");
		$this->getServer()->shutdown();
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