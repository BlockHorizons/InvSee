<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

use BlockHorizons\InvSee\Loader;
use BlockHorizons\InvSee\module\utils\ModuleCommand;
use BlockHorizons\InvSee\module\utils\ModuleUtils;
use Closure;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\event\EventPriority;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function spl_object_id;
use function var_dump;

final class PortableEnderChestModule implements Module, CommandExecutor{

	public static function fromConfiguration(array $configuration) : Module{
		return new self(
			ModuleCommand::parse($configuration["command"], [
				"usage" => "/{$configuration["command"]["name"]} - Access your ender chest",
				"permission" => ["description" => "Grants permission to /{$configuration["command"]["name"]} command"]
			])
		);
	}

	private Loader $loader;

	/**
	 * @var Closure
	 *
	 * @phpstan-var Closure() : void
	 */
	private Closure $event_unregister;

	/** @var array<string, int> */
	private array $viewing = [];

	public function __construct(
		private ModuleCommand $command
	){}

	public function onEnable(Loader $loader) : void{
		$this->command->setup($loader, $this);
		$this->loader = $loader;

		$loader->getServer()->getPluginManager()->registerEvent(InventoryCloseEvent::class, $event = function(InventoryCloseEvent $event) : void{
			if(isset($this->viewing[$uuid = $event->getPlayer()->getUniqueId()->getBytes()]) && $this->viewing[$uuid] === spl_object_id($event->getInventory())){
				unset($this->viewing[$uuid]);
			}
		}, EventPriority::MONITOR, $loader);
		$this->event_unregister = ModuleUtils::getEventListenerUnregisterExecutor(InventoryCloseEvent::class, EventPriority::MONITOR, $event);
	}

	public function onDisable(Loader $loader) : void{
		$this->command->destroy($loader);

		$server = $loader->getServer();
		foreach($this->viewing as $uuid => $_){
			$server->getPlayerByRawUUID($uuid)?->removeCurrentWindow();
		}
		$this->viewing = [];

		($this->event_unregister)();
		unset($this->event_unregister);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "This command can only be used by a player");
			return true;
		}

		if(isset($this->viewing[$uuid = $sender->getUniqueId()->getBytes()])){
			return true;
		}

		$menu = $this->loader->getPlayerList()->getOrCreate($sender->getName())->getEnderChestInventoryMenu();
		$this->viewing[$uuid] = spl_object_id($menu->getInventory());
		$menu->send($sender, "Ender Chest", function(bool $success) use($uuid) : void{
			if(!$success){
				unset($this->viewing[$uuid]);
			}
		});
		return true;
	}
}