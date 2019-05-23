<?php
namespace BlockHorizons\InvSee\inventories\listeners;

use BlockHorizons\InvSee\InventoryHandler;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryChangeListener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;

class SyncInventoryChangeListener implements InventoryChangeListener {

	/** @var InventoryHandler */
	private static $handler;

	public static function init(InventoryHandler $handler): void {
		self::$handler = $handler;
	}

	/** @var Player */
	private $player;

	/** @var Item[] */
	private $contents;

	public function __construct(Player $player, Inventory $inventory) {
		$this->player = $player;
		$this->contents = $inventory->getContents(true);
	}

	public function onSlotChange(Inventory $inventory, int $slot): void {
		$old = $this->contents[$slot];
		$new = $this->contents[$slot] = $inventory->getItem($slot);
		self::$handler->syncPlayerAction($this->player, new SlotChangeAction($inventory, $slot, $old, $new));
	}

	public function onContentChange(Inventory $inventory): void {
		$old = $this->contents;
		$this->contents = $inventory->getContents(true);
		foreach($this->contents as $slot => $new) {
			self::$handler->syncPlayerAction($this->player, new SlotChangeAction($inventory, $slot, $old[$slot], $new));
		}
	}
}