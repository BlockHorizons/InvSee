<?php
namespace BlockHorizons\InvSee\inventories;

use muqsit\invmenu\inventories\ChestInventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;

interface InvSeeInventory {

	/**
	 * Returns the username of the player
	 * we are spying.
	 *
	 * @return string
	 */
	public function getSpying(): string;

	/**
	 * Returns whether this inventory can spy on
	 * the given inventory.
	 *
	 * @param Inventory $inventory
	 *
	 * @return bool
	 */
	public function canSpyInventory(Inventory $inventory): bool;

	/**
	 * Returns whether this slot can be modified.
	 * For player inventory, only slots below
	 * 36 or armor slots can be modified.
	 *
	 * @param Player $player who is modifying the
	 * slot.
	 * @param int $slot that is being modified.
	 *
	 * @return bool
	 */
	public function canModifySlot(Player $player, int $slot): bool;

	/**
	 * Force syncs player's inventory contents
	 * with this inventory.
	 */
	public function syncOnline(Player $player): void;

	/**
	 * Force syncs an offline player's inventory
	 * contents with this inventory.
	 */
	public function syncOffline(): void;

	/**
	 * Syncs inventory action committed by
	 * the player we are spying.
	 *
	 * @param SlotChangeAction $action
	 */
	public function syncPlayerAction(SlotChangeAction $action): void;

	/**
	 * Syncs inventory action committed by
	 * the spyer when the player we are
	 * spying is online.
	 *
	 * @param Player $player
	 * @param SlotChangeAction $action
	 */
	public function syncSpyerAction(Player $player, SlotChangeAction $action): void;

	/**
	 * Returns the inventory contents of the
	 * player we are spying to initialize the
	 * inventory's contents.
	 *
	 * @return Item[]
	 */
	public function getSpyerContents(): array;
}