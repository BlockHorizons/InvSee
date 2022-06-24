<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

use BlockHorizons\InvSee\Loader;
use BlockHorizons\InvSee\utils\config\Configuration;
use BlockHorizons\InvSee\utils\config\ConfigurationException;
use InvalidArgumentException;
use Logger;
use pocketmine\utils\Config;
use PrefixedLogger;
use RuntimeException;

final class ModuleManager{

	private Configuration $config;
	private Logger $logger;

	/** @var array<string, ModuleInfo> */
	private array $modules = [];

	/** @var array<string, Module> */
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
		$this->register(new ModuleInfo(
			"portable-ender-chest",
			"Portable Ender Chest",
			"Allows players to portably access ender inventory via command",
			PortableEnderChestModule::class
		));

		$this->loader->saveResource("modules.yml");
		$this->config = Configuration::fromConfig(new Config($this->loader->getDataFolder() . "modules.yml"));
		$this->logger = new PrefixedLogger($this->loader->getLogger(), "Module Manager");
	}

	public function init() : void{
		foreach($this->config["module-states"] as $identifier => $state){
			if($state === "enabled"){
				$this->enable($this->get($identifier));
			}elseif($state !== "disabled"){
				$this->config->throwUndefinedConfiguration($identifier, "State must be either \"enabled\" or \"disabled\", got \"{$state}\" for module {$identifier}");
			}
		}
	}

	public function register(ModuleInfo $info) : void{
		if(isset($this->modules[$info->identifier])){
			throw new InvalidArgumentException("A module with the identifier {$info->identifier} is already registered");
		}

		$this->modules[$info->identifier] = $info;
	}

	public function get(string $identifier) : ModuleInfo{
		return $this->modules[$identifier];
	}

	public function getNullable(string $identifier) : ?ModuleInfo{
		return $this->modules[$identifier] ?? null;
	}

	/**
	 * @return array<string, ModuleInfo>
	 */
	public function getAll() : array{
		return $this->modules;
	}

	public function getLogger() : Logger{
		return $this->logger;
	}

	public function isEnabled(ModuleInfo $info) : bool{
		if(!isset($this->modules[$info->identifier])){
			throw new RuntimeException("Invalid module: {$info->identifier}");
		}

		return isset($this->enabled[$info->identifier]);
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

		try{
			$module = $info->module_class::fromConfiguration($configuration ?? $this->config[$info->identifier]);
		}catch(ConfigurationException $e){
			$this->loader->onConfigurationException($e);
			return;
		}

		$this->enabled[$info->identifier] = $module;
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