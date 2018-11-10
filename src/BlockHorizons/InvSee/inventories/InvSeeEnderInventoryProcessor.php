<?php
namespace BlockHorizons\InvSee\inventories;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryEventProcessor;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;

class InvSeeEnderInventoryProcessor implements InventoryEventProcessor {

	/** @var Player */
	private $player;

	public function __construct(Player $player) {
		$this->player = $player;
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $oldItem, Item $newItem): ?Item {
		Server::getInstance()->getPluginManager()->getPlugin("InvSee")->getInventoryHandler()->syncPlayerAction($this->player, new SlotChangeAction($inventory, $slot, $oldItem, $newItem));
		return $newItem;
	}
}