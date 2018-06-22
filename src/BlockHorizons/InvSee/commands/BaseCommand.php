<?php
namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\Loader;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand {

	const FLAG_DENY_CONSOLE = 0b10000000000000;

	/**
	 * Registers all the default InvSee commands.
	 *
	 * @param Loader $loader
	 */
	public static function registerDefaults(Loader $loader): void {
		$commands = [];

		foreach([
			EnderInvSeeCommand::class => ["enderinvsee", "View a player's ender chest inventory.", "/enderinvsee <player>", "invsee.enderinventory.view"],
			InvSeeCommand::class => ["invsee", "View a player's inventory.", "/invsee <player>", "invsee.inventory.view"]
		] as $class => [$name, $desc, $usage, $perm]) {
			$commands[$name] = new $class($loader, $name, $desc, $usage);
			$commands[$name]->setPermission($perm);
		}

		$loader->getServer()->getCommandMap()->registerAll($loader->getName(), $commands);
	}

	/** @var Loader */
	protected $loader;

	/** @var int */
	protected $flags = 0;

	public function __construct(Loader $loader, string $name, string $description = "", string $usageMessage = "", array $aliases = []) {
		if($usageMessage !== "") {
			$usageMessage = TextFormat::RED . "Usage: " . $usageMessage;
		}

		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->loader = $loader;

		$this->initCommand();
	}

	protected function initCommand(): void {
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}

	/**
	 * @return Loader
	 */
	public function getPlugin(): Plugin {
		return $this->plugin;
	}

	/**
	 * @param int $flag
	 */
	protected function setFlag(int $flag): void {
		if(!$this->isFlagSet($flag)) {
			$this->flags |= $flag;
		}
	}

	/**
	 * @param int $flag
	 */
	protected function removeFlag(int $flag): void {
		if($this->isFlagSet($flag)) {
			$this->flags &= ~$flag;
		}
	}

	/**
	 * @param int $flag
	 */
	protected function isFlagSet(int $flag): bool {
		return ($this->flags & $flag) === $flag;
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 *
	 * @return mixed
	 */
	final public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if($sender instanceof ConsoleCommandSender && $this->isFlagSet(self::FLAG_DENY_CONSOLE)) {
			$sender->sendMessage(TextFormat::RED . "You cannot use this command thru console.");
			return;
		}

		if(!$this->testPermissionSilent($sender)) {
			$this->sendPermissionMessage($sender);
			return;
		}

		if(!$this->onCommand($sender, $commandLabel, $args) && $this->usageMessage !== "") {
			$sender->sendMessage(str_replace("/" . $this->getName(), "/" . $commandLabel, $this->getUsage()));
			return;
		}
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param string[]      $args
	 *
	 * @return bool
	 */
	abstract public function onCommand(CommandSender $sender, string $commandLabel, array $args): bool;
}