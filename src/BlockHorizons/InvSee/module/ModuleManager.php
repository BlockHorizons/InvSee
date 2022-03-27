<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

use BlockHorizons\InvSee\Loader;
use InvalidArgumentException;
use Logger;
use pocketmine\utils\Config;
use PrefixedLogger;
use RuntimeException;

final class ModuleManager{

	private Config $config;
	private Logger $logger;

	/**
	 * @var ModuleInfo[]
	 *
	 * @phpstan-var array<string, ModuleInfo>
	 */
	private array $modules = [];

	/**
	 * @var Module[]
	 *
	 * @phpstan-var array<string, Module>
	 */
	private array $enabled = [];

	public function __construct(
		private Loader $loader
	){
		$this->register(new ModuleInfo(
			"invsee-by-request",
			"InvSee by Request",
			"Allows players to request other players to view their inventory",
			InvSeeByRequestModule::class
		));

		$this->loader->saveResource("modules.yml");
		$this->config = new Config($this->loader->getDataFolder() . "modules.yml");
		$this->logger = new PrefixedLogger($this->loader->getLogger(), "Module Manager");

		// TODO: A command to manage all modules - modules can be enabled and disabled dynamically
	}

	public function init() : void{
		foreach($this->config->get("module-states") as $identifier => $state){
			if($state === "enabled"){
				$this->enable($this->getModule($identifier));
			}elseif($state !== "disabled"){
				throw new RuntimeException("State must be either \"enabled\" or \"disabled\", got \"{$state}\" for module {$identifier}");
			}
		}
	}

	public function register(ModuleInfo $info) : void{
		if(isset($this->modules[$info->identifier])){
			throw new InvalidArgumentException("A module with the identifier {$info->identifier} is already registered");
		}

		$this->modules[$info->identifier] = $info;
	}

	public function getModule(string $identifier) : ModuleInfo{
		return $this->modules[$identifier];
	}

	public function getModuleNullable(string $identifier) : ?ModuleInfo{
		return $this->modules[$identifier] ?? null;
	}

	/**
	 * @return ModuleInfo[]
	 *
	 * @phpstan-return array<string, ModuleInfo>
	 */
	public function getModules() : array{
		return $this->modules;
	}

	public function getLogger() : Logger{
		return $this->logger;
	}

	/**
	 * @param ModuleInfo $info
	 * @param mixed[]|null $configuration
	 */
	public function enable(ModuleInfo $info, ?array $configuration = null) : void{
		if(!isset($this->modules[$info->identifier])){
			throw new RuntimeException("Invalid module: {$info->identifier}");
		}

		if(isset($this->enabled[$info->identifier])){
			throw new RuntimeException("Module {$info->identifier} is already enabled");
		}

		$configuration ??= $this->config->get($info->identifier, []);
		$this->enabled[$info->identifier] = $info->module_class::fromConfiguration($configuration);
		$this->enabled[$info->identifier]->onEnable($this->loader);
		$this->logger->debug("Enabled module: {$info->identifier}");
	}

	public function disable(ModuleInfo $info) : void{
		if(!isset($this->modules[$info->identifier])){
			throw new RuntimeException("Invalid module: {$info->identifier}");
		}

		if(!isset($this->enabled[$info->identifier])){
			throw new RuntimeException("Module {$info->identifier} is already disabled");
		}

		$this->enabled[$info->identifier]->onDisable($this->loader);
		unset($this->enabled[$info->identifier]);
		$this->logger->debug("Disabled module: {$info->identifier}");
	}
}