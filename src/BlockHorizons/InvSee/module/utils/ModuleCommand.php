<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module\utils;

use BlockHorizons\InvSee\Loader;
use BlockHorizons\InvSee\utils\config\Configuration;
use BlockHorizons\InvSee\utils\config\UndefinedConfigurationException;
use pocketmine\command\CommandExecutor;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use RuntimeException;
use function implode;

final class ModuleCommand{

	public static function parse(Configuration ...$configurations) : self{
		$get_config = static function(string ...$identifiers) use($configurations) : mixed{
			foreach($configurations as $configuration){
				try{
					return $configuration->get(...$identifiers);
				}catch(UndefinedConfigurationException){
				}
			}
			throw new RuntimeException("Config identifier " . implode(".", $identifiers) . " is not set");
		};

		return new self(
			$get_config("name"),
			$get_config("usage"),
			$get_config("permission", "name"),
			$get_config("permission", "description"),
			$get_config("permission", "access"),
			$get_config("aliases")
		);
	}

	private ?PluginCommand $command = null;
	private ?Permission $permission = null;

	/**
	 * @param string $name
	 * @param string $usage
	 * @param string $permission_name
	 * @param string $permission_description
	 * @param string $permission_accessibility
	 * @param list<string> $aliases
	 */
	public function __construct(
		readonly public string $name,
		readonly public string $usage,
		readonly public string $permission_name,
		readonly public string $permission_description,
		readonly public string $permission_accessibility,
		readonly public array $aliases
	){}

	public function setup(Loader $loader, CommandExecutor $executor) : PluginCommand{
		// Permission registration
		$permission_manager = PermissionManager::getInstance();
		$command_permission = new Permission($this->permission_name, $this->permission_description);
		$permission_manager->addPermission($command_permission) || throw new RuntimeException("Permission {$command_permission->getName()} is already registered");
		ModuleUtils::assignPermissionDefault($command_permission, $this->permission_accessibility);
		$this->permission = $command_permission;

		// Command registration
		$command_manager = $loader->getServer()->getCommandMap();
		$command = new PluginCommand($this->name, $loader, $executor);
		$command->setPermission($command_permission->getName());
		$command->setUsage($this->usage);
		$command->setAliases($this->aliases);
		$command_manager->register($loader->getName(), $command);
		$this->command = $command;
		return $command;
	}

	public function destroy(Loader $loader) : void{
		PermissionManager::getInstance()->removePermission($this->permission ?? throw new RuntimeException("Cannot retrieve permission: {$this->permission_name}"));
		$this->permission = null;

		$loader->getServer()->getCommandMap()->unregister($this->command ?? throw new RuntimeException("Cannot retrieve command: /{$this->name}"));
		$this->command = null;
	}
}