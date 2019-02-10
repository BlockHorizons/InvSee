<?php
namespace BlockHorizons\InvSee\inventories;

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

	public function canSpyInventory(Inventory $inventory): bool {
		return $inventory instanceof EnderInventory;
	}

	public function canModifySlot(Player $player, int $slot): bool {
		return $player->hasPermission($this->getSpying() === $player->getLowerCaseName() ? "invsee.enderinventory.modify.self" : "invsee.enderinventory.modify");
	}

	protected function installSlotChangeListener(Player $player): void {
		$inventory = $player->getEnderChestInventory();
		if($inventory->getSlotChangeListener() !== null) {
			throw new \BadMethodCallException("Tried overriding an already existing slot change listener.");
		}

		$inventory_handler = Server::getInstance()->getPluginManager()->getPlugin("InvSee")->getInventoryHandler();
		$inventory->setSlotChangeListener(function(Inventory $inventory, int $slot, Item $oldItem, Item $newItem) use($player, $inventory_handler): ?Item {
			$inventory_handler->syncPlayerAction($player, new SlotChangeAction($inventory, $slot, $oldItem, $newItem));
			return $newItem;
		});
	}

	protected function uninstallSlotChangeListener(Player $player): void {
		$player->getEnderChestInventory()->setSlotChangeListener(null);
	}

	public function initialize(SpyingPlayerData $data): void {
		$player = $data->getPlayer();
		if($player !== null) {
			$this->installSlotChangeListener($player);
		}
	}

	public function deInitialize(SpyingPlayerData $data): void {
		$player = $data->getPlayer();
		if($player !== null) {
			$this->uninstallSlotChangeListener($player);
		}
	}

	public function syncOnline(Player $player): void {
		$player->getEnderChestInventory()->setContents($this->getContents());
		$this->installSlotChangeListener($player);
	}

	public function syncOffline(): void {
		$server = Server::getInstance();

		$contents = [];
		foreach($this->getContents() as $slot => $item) {
			$contents[] = $item->nbtSerialize($slot);
		}

		$nbt = $server->getOfflinePlayerData($this->spying) ?? new CompoundTag();
		$nbt->setTag(new ListTag("EnderChestInventory", $contents));
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
				foreach($ender_chest_inventory as $nbt) {
					$contents[$nbt->getByte("Slot")] = Item::nbtDeserialize($nbt);
				}
			}
		}

		return $contents;
	}
}