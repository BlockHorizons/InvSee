<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

use BlockHorizons\InvSee\commands\EnderInvSeeCommandExecutor;
use BlockHorizons\InvSee\commands\InvSeeCommandExecutor;
use BlockHorizons\InvSee\Loader;
use BlockHorizons\InvSee\module\utils\ModuleUtils;
use BlockHorizons\InvSee\utils\config\Configuration;
use Closure;
use InvalidArgumentException;
use Logger;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use PrefixedLogger;
use RuntimeException;
use function assert;
use function ceil;
use function count;
use function hrtime;
use function is_infinite;
use function strtolower;

final class InvSeeByRequestModule implements Module, CommandExecutor{

	public static function fromConfiguration(Configuration $configuration) : Module{
		return new self(
			$configuration["request-command"]["name"],
			$configuration["request-command"]["permission"]["name"],
			$configuration["request-command"]["permission"]["access"],
			$configuration["grant-command"]["name"],
			$configuration["grant-command"]["permission"]["name"],
			$configuration["grant-command"]["permission"]["access"],
			$configuration["request-timeout"],
			$configuration["grant-timeout"]
		);
	}

	private Loader $loader;
	private Logger $logger;
	private ?TaskHandler $revoker = null;

	/**
	 * @var Closure
	 *
	 * @phpstan-var Closure() : void
	 */
	private Closure $event_unregister;

	/**
	 * @var Closure
	 *
	 * @phpstan-var Closure(Player, string) : ?bool
	 */
	private Closure $checker;

	/**
	 * @var array<int, array<int, float>>
	 */
	private array $requests = [];

	/**
	 * @var array<string, int>
	 */
	private array $access_player_expiry = [];

	/**
	 * @var array<int, array<string, string>>
	 */
	private array $access_expiry_player_viewing = [];

	public function __construct(
		private string $request_command_name,
		private string $request_command_permission,
		private string $request_command_permission_accessibility,
		private string $grant_command_name,
		private string $grant_command_permission,
		private string $grant_command_permission_accessibility,
		private float $request_timeout,
		private float $grant_timeout
	){
		if($this->request_command_name === $this->grant_command_name){
			throw new InvalidArgumentException("Request command name and grant command name must not be the same");
		}
		if($this->request_timeout < 0.0){
			throw new InvalidArgumentException("Request timeout cannot be less than 0.0");
		}
		if($this->grant_timeout < 0.0){
			throw new InvalidArgumentException("Grant timeout cannot be less than 0.0");
		}
	}

	/**
	 * @param Loader $loader
	 * @param string $command_name
	 * @param string $executor_class
	 * @return CommandExecutor
	 *
	 * @phpstan-template TCommandExecutor of CommandExecutor
	 * @phpstan-param class-string<TCommandExecutor> $executor_class
	 * @phpstan-return TCommandExecutor
	 */
	private function getCommandExecutor(Loader $loader, string $command_name, string $executor_class) : CommandExecutor{
		$command = $loader->getCommand($command_name);
		if(!($command instanceof PluginCommand)){
			throw new RuntimeException("Command \"{$command_name}\" is not registered");
		}
		$inv_see_command_executor = $command->getExecutor();
		if(!($inv_see_command_executor instanceof $executor_class)){
			throw new RuntimeException("Command \"{$command_name}\" 's executor is not {$executor_class}");
		}
		return $inv_see_command_executor;
	}

	private function revokePlayerPermission(Player $player) : void{
		if(!isset($this->access_player_expiry[$id = $player->getUniqueId()->getBytes()])){
			return;
		}

		$viewing_inventory = $player->getCurrentWindow();
		$viewing = $this->access_expiry_player_viewing[$expiry = $this->access_player_expiry[$id]][$id];
		unset($this->access_player_expiry[$id], $this->access_expiry_player_viewing[$expiry][$id]);
		if(count($this->access_expiry_player_viewing[$expiry]) === 0){
			unset($this->access_expiry_player_viewing[$expiry]);
		}

		$player_data = $this->loader->getPlayerList()->get($viewing);
		if(
			$player_data !== null && (
				$player_data->getInventoryMenu()->getInventory() === $viewing_inventory ||
				$player_data->getEnderChestInventoryMenu()->getInventory() === $viewing_inventory
			)
		){
			$player->removeCurrentWindow();
			$player->sendMessage(TextFormat::RED . "You may no longer view {$player_data->getPlayer()}'s inventory.");
		}

		$this->logger->debug("Revoked {$player->getName()} from accessing {$viewing}");
	}

	private function grantPlayerPermission(Player $player, Player $viewing) : void{
		$this->revokePlayerPermission($player);

		$server = Server::getInstance();
		$until = $server->getTick() + ((int) ceil($this->grant_timeout * 20));
		$this->access_player_expiry[$uuid = $player->getUniqueId()->getBytes()] = $until;
		$this->access_expiry_player_viewing[$until][$uuid] = strtolower($viewing->getName());

		if(is_infinite($this->grant_timeout) || $this->revoker !== null){
			return;
		}

		$this->logger->debug("Started revoker");
		$this->revoker = $this->loader->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use($server) : void{
			if(isset($this->access_expiry_player_viewing[$expiry = $server->getTick()])){
				foreach($this->access_expiry_player_viewing[$expiry] as $player_id => $viewing){
					$player = $server->getPlayerByRawUUID($player_id);
					assert($player !== null);
					$this->revokePlayerPermission($player);
				}
			}
			if(count($this->access_expiry_player_viewing) === 0){
				$this->revoker = null;
				$this->logger->debug("Stopped revoker");
				throw new CancelTaskException("No accessors to check for");
			}
		}), 1);
	}

	private function hasPlayerPermission(Player $player, string $viewing) : bool{
		if(isset($this->access_player_expiry[$id = $player->getUniqueId()->getBytes()])){
			$expiry = $this->access_player_expiry[$id];
			if($this->access_expiry_player_viewing[$expiry][$id] === strtolower($viewing)){
				return true;
			}
		}
		return false;
	}

	public function onEnable(Loader $loader) : void{
		// Permission registration
		$permission_manager = PermissionManager::getInstance();
		$request_command_permission = new Permission($this->request_command_permission, "Grants permission to /{$this->request_command_name} command");
		if(!$permission_manager->addPermission($request_command_permission)){
			throw new RuntimeException("Permission {$request_command_permission->getName()} is already registered");
		}
		ModuleUtils::assignPermissionDefault($request_command_permission, $this->request_command_permission_accessibility);

		$grant_command_permission = new Permission($this->grant_command_permission, "Grants permission to /{$this->grant_command_permission} command");
		if(!$permission_manager->addPermission($grant_command_permission)){
			throw new RuntimeException("Permission {$grant_command_permission->getName()} is already registered");
		}
		ModuleUtils::assignPermissionDefault($grant_command_permission, $this->grant_command_permission_accessibility);


		// Command registration
		$command_manager = $loader->getServer()->getCommandMap();

		$request_command = new PluginCommand($this->request_command_name, $loader, $this);
		$request_command->setPermission($request_command_permission->getName());
		$request_command->setUsage("/{$request_command->getName()} <player> - Request <player> access to view their inventory");
		$command_manager->register($loader->getName(), $request_command);

		$grant_command = new PluginCommand($this->grant_command_name, $loader, $this);
		$grant_command->setPermission($grant_command_permission->getName());
		$grant_command->setUsage("/{$grant_command->getName()} <player> - Grant <player> access to view your inventory");
		$command_manager->register($loader->getName(), $grant_command);


		// Session registration
		$loader->getServer()->getPluginManager()->registerEvent(PlayerQuitEvent::class, $event = function(PlayerQuitEvent $event) : void{
			$player = $event->getPlayer();
			unset($this->requests[$player->getId()]);
			$this->revokePlayerPermission($player);
		}, EventPriority::MONITOR, $loader);
		$this->event_unregister = ModuleUtils::getEventListenerUnregisterExecutor(PlayerQuitEvent::class, EventPriority::MONITOR, $event);

		$this->checker = function(Player $player, string $viewing) : ?bool{
			return $this->hasPlayerPermission($player, $viewing) ? true : null;
		};
		$this->getCommandExecutor($loader, "invsee", InvSeeCommandExecutor::class)->getViewPermissionChecker()->register($this->checker, -1);
		$this->getCommandExecutor($loader, "enderinvsee", EnderInvSeeCommandExecutor::class)->getViewPermissionChecker()->register($this->checker, -1);

		$this->loader = $loader;
		$this->logger = new PrefixedLogger($loader->getModuleManager()->getLogger(), "InvSee by Request");
	}

	public function onDisable(Loader $loader) : void{
		$server = $loader->getServer();
		foreach($this->access_player_expiry as $id => $_){
			$player = $server->getPlayerByRawUUID($id);
			assert($player !== null);
			$this->revokePlayerPermission($player);
		}

		$permission_manager = PermissionManager::getInstance();
		$permission_manager->removePermission($permission_manager->getPermission($this->request_command_permission) ?? throw new RuntimeException("Cannot retrieve permission: {$this->request_command_permission}"));
		$permission_manager->removePermission($permission_manager->getPermission($this->grant_command_permission) ?? throw new RuntimeException("Cannot retrieve permission: {$this->grant_command_permission}"));

		$command_manager = $loader->getServer()->getCommandMap();
		$command_manager->unregister($command_manager->getCommand($this->request_command_name) ?? throw new RuntimeException("Cannot retrieve command: /{$this->request_command_name}"));
		$command_manager->unregister($command_manager->getCommand($this->grant_command_name) ?? throw new RuntimeException("Cannot retrieve command: /{$this->grant_command_name}"));

		$this->getCommandExecutor($loader, "invsee", InvSeeCommandExecutor::class)->getViewPermissionChecker()->unregister($this->checker);
		$this->getCommandExecutor($loader, "enderinvsee", EnderInvSeeCommandExecutor::class)->getViewPermissionChecker()->unregister($this->checker);

		$this->revoker?->cancel();
		$this->revoker = null;

		($this->event_unregister)();
		unset($this->event_unregister, $this->checker, $this->loader, $this->logger); // de-initialize properties
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case $this->request_command_name:
				if(!($sender instanceof Player)){
					$sender->sendMessage(TextFormat::RED . "This command can only be used by a player");
					return true;
				}

				if(!isset($args[0])){
					return false;
				}

				$player = $sender->getServer()->getPlayerByPrefix($args[0]);
				if($player === null){
					$sender->sendMessage(TextFormat::RED . "Player {$args[0]} is offline");
					return true;
				}

				if($this->hasPlayerPermission($sender, $player->getName())){
					$sender->sendMessage(TextFormat::RED . "You already have access to view {$player->getName()}'s inventory.");
					return true;
				}

				if(isset($this->requests[$player->getId()][$sender->getId()]) && hrtime(true) - $this->requests[$player->getId()][$sender->getId()] < $this->request_timeout * 1_000_000_000){
					$sender->sendMessage(TextFormat::RED . "You have already requested {$player->getName()} for view access.");
					return true;
				}

				$this->requests[$player->getId()][$sender->getId()] = hrtime(true);
				$player->sendMessage(TextFormat::YELLOW . "{$sender->getName()} wants to view your inventory.");
				$player->sendMessage(TextFormat::GRAY . "Run " . TextFormat::YELLOW . "/{$this->grant_command_name} {$sender->getName()}" . TextFormat::GRAY . " to accept their request.");
				$sender->sendMessage(TextFormat::GREEN . "Sent {$player->getName()} request to view their inventory.");
				return true;
			case $this->grant_command_name:
				if(!($sender instanceof Player)){
					$sender->sendMessage(TextFormat::RED . "This command can only be used by a player");
					return true;
				}

				if(!isset($args[0])){
					return false;
				}

				$player = $sender->getServer()->getPlayerByPrefix($args[0]);
				if($player === null){
					$sender->sendMessage(TextFormat::RED . "Player {$args[0]} is offline");
					return true;
				}

				if(!isset($this->requests[$sender->getId()][$player->getId()])){
					$sender->sendMessage(TextFormat::RED . "You have not received any requests from {$player->getName()}.");
					return true;
				}

				$time_diff = hrtime(true) - $this->requests[$sender->getId()][$player->getId()];
				unset($this->requests[$sender->getId()][$player->getId()]);
				if(count($this->requests[$sender->getId()]) === 0){
					unset($this->requests[$sender->getId()]);
				}

				if($time_diff > $this->request_timeout * 1_000_000_000){
					$sender->sendMessage(TextFormat::RED . "{$player->getName()}'s request has expired.");
					return true;
				}

				$this->grantPlayerPermission($player, $sender);

				$player->sendMessage(TextFormat::GREEN . "{$sender->getName()} has accepted your request to view their inventory.");
				$player->sendMessage(TextFormat::GRAY . "Run " . TextFormat::GREEN . "/invsee {$sender->getName()}" . TextFormat::GRAY . " to view their inventory.");
				$player->sendMessage(TextFormat::GRAY . "Run " . TextFormat::GREEN . "/enderinvsee {$sender->getName()}" . TextFormat::GRAY . " to view their ender chest inventory.");
				$sender->sendMessage(TextFormat::GREEN . "You have granted {$player->getName()} access to view your inventory.");
				return true;
		}

		throw new RuntimeException("Unexpected command: /{$command->getName()}");
	}
}