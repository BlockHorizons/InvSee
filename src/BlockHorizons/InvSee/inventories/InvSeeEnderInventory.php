<?php
namespace BlockHorizons\InvSee\inventories;

use BlockHorizons\InvSee\inventories\listeners\SyncInventoryChangeListener;
use BlockHorizons\InvSee\utils\SpyingPlayerData;

use muqsit\invmenu\inventories\ChestInventory;

use pocketmine\inventory\EnderInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\Server;

class InvSeeEnderInventory extends ChestInventory implements InvSeeInventory {
	use InvSeeInventoryTrait;

	/** @var SyncInventoryChangeLisetner|null */
	private $inventory_listener;

	public function canSpyInventory(Inventory $inventory): bool {
		return $inventory instanceof EnderInventory;
	}

	public function canModifySlot(Player $player, int $slot): bool {
		return $player->hasPermission($this->getSpying() === $player->getLowerCaseName() ? "invsee.enderinventory.modify.self" : "invsee.enderinventory.modify");
	}

	protected function installChangeListener(Player $player): void {
		$inventory = $player->getEnderChestInventory();
		$inventory->addChangeListeners($this->inventory_listener = new SyncInventoryChangeListener($player, $inventory));
	}

	protected function uninstallChangeListener(Player $player): void {
		$player->getEnderChestInventory()->removeChangeListeners($this->inventory_listener);
	}

	public function initialize(SpyingPlayerData $data): void {
		$player = $data->getPlayer();
		if($player !== null) {
			$this->installChangeListener($player);
		}
	}

	public function deInitialize(SpyingPlayerData $data): void {
		$player = $data->getPlayer();
		if($player !== null) {
			$this->uninstallChangeListener($player);
		}
	}

	public function syncOnline(Player $player): void {
		$player->getEnderChestInventory()->setContents($this->getContents());
		$this->installChangeListener($player);
	}

	public function syncOffline(): void {
		$server = Server::getInstance();

		$contents = [];
		foreach($this->getContents() as $slot => $item) {
			$contents[] = $item->nbtSerialize($slot);
		}

		$nbt = $server->getOfflinePlayerData($this->spying) ?? CompoundTag::create();
		$nbt->setTag("EnderChestInventory", new ListTag($contents));
		$server->saveOfflinePlayerData($this->spying, $nbt);
	}

	public function syncPlayerAction(SlotChangeAction $action): void {
		$this->setItem($action->getSlot(), $action->getTargetItem());
	}

	public function syncSpyerAction(Player $spying, SlotChangeAction $action): void {
		$spying->getEnderChestInventory()->setItem($action->getSlot(), $action->getTargetItem());
	}

	public function getSpyerContents(): array {
		$server = Server::getInstance();
		$player_instance = $server->getPlayerExact($this->spying);

		if($player_instance !== null) {
			return $player_instance->getEnderChestInventory()->getContents();
		}

		$contents = [];
		$data = $server->getOfflinePlayerData($this->spying);
		if($data !== null) {
			$ender_chest_inventory = $data->getListTag("EnderChestInventory");
			if($ender_chest_inventory !== null) {
				foreach($ender_chest_inventory->getValue() as $nbt) {
					$contents[$nbt->getByte("Slot")] = Item::nbtDeserialize($nbt);
				}
			}
		}

		return $contents;
	}
}
