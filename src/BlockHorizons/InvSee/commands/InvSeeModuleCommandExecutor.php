<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\module\ModuleInfo;
use BlockHorizons\InvSee\module\ModuleManager;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_keys;
use function implode;

final class InvSeeModuleCommandExecutor implements CommandExecutor{

	public function __construct(
		readonly private ModuleManager $manager
	){}

	private function getModule(CommandSender $sender, string $identifier) : ?ModuleInfo{
		$module = $this->manager->getNullable($identifier);
		if($module !== null){
			return $module;
		}

		$modules = $this->manager->getAll();
		$sender->sendMessage(TextFormat::RED . "No module with the identifier \"{$identifier}\" exists.");
		$sender->sendMessage(TextFormat::RED . "Available modules (" . count($modules) . "): " . implode(", ", array_keys($modules)));
		return null;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(isset($args[0])){
			switch($args[0]){
				case "list":
					$modules = $this->manager->getAll();
					$modules_c = count($modules);
					$sender->sendMessage(TextFormat::YELLOW . "There " . ($modules_c === 1 ? "is" : "are") . " {$modules_c} module" . ($modules_c === 1 ? "" : "s") . ":");
					$i = 0;
					foreach($this->manager->getAll() as $module_info){
						$sender->sendMessage(TextFormat::BOLD . TextFormat::YELLOW . ++$i . ". " . TextFormat::RESET . TextFormat::YELLOW . $module_info->identifier . TextFormat::GRAY . " [" . ($this->manager->isEnabled($module_info) ? TextFormat::GREEN . "ENABLED" : TextFormat::RED . "DISABLED") . TextFormat::GRAY . "]");
					}
					return true;
				case "info":
					if(!isset($args[1])){
						$sender->sendMessage(TextFormat::RED . "/{$label} {$args[0]} <module>");
						return true;
					}

					$module_info = $this->getModule($sender, $args[1]);
					if($module_info === null){
						return true;
					}

					$sender->sendMessage(
						TextFormat::YELLOW . TextFormat::BOLD . "Module Information" . TextFormat::RESET . TextFormat::EOL .
						TextFormat::YELLOW . "Identifier: " . TextFormat::GRAY . $module_info->identifier . TextFormat::EOL .
						TextFormat::YELLOW . "Name: " . TextFormat::GRAY . $module_info->name . TextFormat::EOL .
						TextFormat::YELLOW . "Description: " . TextFormat::GRAY . $module_info->description . TextFormat::EOL .
						TextFormat::YELLOW . "State: " . ($this->manager->isEnabled($module_info) ? TextFormat::GREEN . "ENABLED" : TextFormat::RED . "DISABLED")
					);
					return true;
				case "enable":
					if(!isset($args[1])){
						$sender->sendMessage(TextFormat::RED . "/{$label} {$args[0]} <module>");
						return true;
					}

					$module_info = $this->getModule($sender, $args[1]);
					if($module_info === null){
						return true;
					}

					if($this->manager->isEnabled($module_info)){
						$sender->sendMessage(TextFormat::RED . "Module \"{$module_info->name}\" is already enabled.");
						return true;
					}

					$this->manager->enable($module_info);
					$sender->sendMessage($this->manager->isEnabled($module_info) ? TextFormat::GREEN . "Module \"{$module_info->name}\" is now enabled." :
						TextFormat::RED . "An error occurred while enabling module \"{$module_info->name}\"."
					);
					return true;
				case "disable":
					if(!isset($args[1])){
						$sender->sendMessage(TextFormat::RED . "/{$label} {$args[0]} <module>");
						return true;
					}

					$module_info = $this->getModule($sender, $args[1]);
					if($module_info === null){
						return true;
					}

					if(!$this->manager->isEnabled($module_info)){
						$sender->sendMessage(TextFormat::RED . "Module \"{$module_info->name}\" is already disabled.");
						return true;
					}

					$this->manager->disable($module_info);
					$sender->sendMessage(!$this->manager->isEnabled($module_info) ? TextFormat::GREEN . "Module \"{$module_info->name}\" is now disabled." :
						TextFormat::RED . "An error occurred while disabling module \"{$module_info->name}\"."
					);
					return true;
			}
		}

		$sender->sendMessage(
			TextFormat::YELLOW . TextFormat::BOLD . "InvSee Module Command" . TextFormat::RESET . TextFormat::EOL .
			TextFormat::YELLOW . "/{$label} list" . TextFormat::GRAY . " - List all InvSee modules" . TextFormat::EOL .
			TextFormat::YELLOW . "/{$label} info <module>" . TextFormat::GRAY . " - View info about an InvSee module" . TextFormat::EOL .
			TextFormat::YELLOW . "/{$label} enable <module>" . TextFormat::GRAY . " - Enable an InvSee module" . TextFormat::EOL .
			TextFormat::YELLOW . "/{$label} disable <module>" . TextFormat::GRAY . " - Disable an InvSee module"
		);
		return true;
	}
}