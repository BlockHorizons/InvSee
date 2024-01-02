<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module\utils;

use BlockHorizons\InvSee\Loader;
use pocketmine\command\CommandExecutor;
use pocketmine\command\PluginCommand;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use RuntimeException;
use function current;
use function implode;

final class ModuleCommand{

	/**
	 * @param array<string, mixed> ...$configurations
	 * @return self
	 */
	public static function parse(array ...$configurations) : self{
		$get_config = static function(string ...$identifier) use($configurations) : mixed{
			foreach($configurations as $configuration){
				$value = $configuration;
				reset($identifier);
				while(($entry = current($identifier)) !== false){
					if(isset($value[$entry])){
						$value = $value[$entry];
						next($identifier);
					}else{
						continue 2;
					}
				}
				if(current($identifier) === false){
					return $value;
				}
			}
			throw new RuntimeException("Config identifier " . implode(".", $identifier) . " is not set");
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
	 * @param string[] $aliases
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