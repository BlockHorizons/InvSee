<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

abstract class BaseCommand extends Command implements PluginOwned{

	protected const FLAG_DENY_CONSOLE = 0b10000000000000;

	private Loader $loader;
	private int $flags = 0;

	public function __construct(Loader $loader, string $name, string $description = "", string $usageMessage = "", array $aliases = []){
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->loader = $loader;

		$this->initCommand();
	}

	protected function initCommand() : void{
	}

	final public function getLoader() : Loader{
		return $this->loader;
	}

	final public function getOwningPlugin() : Plugin{
		return $this->loader;
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	final public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!$this->loader->isEnabled()){
			return false;
		}

		if($sender instanceof ConsoleCommandSender && $this->isFlagSet(self::FLAG_DENY_CONSOLE)){
			$sender->sendMessage(TextFormat::RED . "You cannot use this command thru console.");
			return false;
		}

		if(!$this->testPermissionSilent($sender)){
			$this->sendPermissionMessage($sender);
			return false;
		}

		if(!$this->onCommand($sender, $commandLabel, $args)){
			if($this->usageMessage !== ""){
				throw new InvalidCommandSyntaxException();
			}

			return false;
		}

		return true;
	}

	public function sendPermissionMessage(CommandSender $sender) : void{
		$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command.");
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param string[] $args
	 *
	 * @return bool
	 */
	abstract public function onCommand(CommandSender $sender, string $commandLabel, array $args) : bool;

	final protected function setFlag(int $flag) : void{
		if(!$this->isFlagSet($flag)){
			$this->flags |= $flag;
		}
	}

	final protected function isFlagSet(int $flag) : bool{
		return ($this->flags & $flag) === $flag;
	}

	final protected function removeFlag(int $flag) : void{
		if($this->isFlagSet($flag)){
			$this->flags &= ~$flag;
		}
	}
}