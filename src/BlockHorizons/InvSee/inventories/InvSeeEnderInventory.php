<?php
namespace BlockHorizons\InvSee\inventories;

use muqsit\invmenu\inventories\ChestInventory;

use pocketmine\inventory\EnderInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\Server;

class InvSeeEnderInventory extends ChestInventory implements InvSeeInventory{
	use InvSeeInventoryTrait;

	public function canSpyInventory(Inventory $inventory) : bool{
		return $inventory instanceof EnderInventory;
	}

	public function canModifySlot(Player $player, int $slot) : bool{
		return $player->hasPermission($this->getSpying() === $player->getLowerCaseName() ? "invsee.enderinventory.modify.self" : "invsee.enderinventory.modify");
	}

	public function syncOnline(Player $player) : void{
		$player->getEnderChestInventory()->setContents($this->getContents());
	}

	public function syncOffline() : void{
		$server = Server::getInstance();

		$contents = [];
		foreach($this->getContents() as $slot => $item){
			$contents[] = $item->nbtSerialize($slot);
		}

		$nbt = $server->getOfflinePlayerData($this->spying);
		$nbt->setTag(new ListTag("EnderChestInventory", $contents));
		$server->saveOfflinePlayerData($this->spying, $nbt);
	}

	public function syncPlayerAction(SlotChangeAction $action) : void{
		$this->setItem($action->getSlot(), $action->getTargetItem());
	}

	public function syncSpyerAction(Player $spying, SlotChangeAction $action) : void{
		$spying->getEnderChestInventory()->setItem($action->getSlot(), $action->getTargetItem());
	}

	public function getSpyerContents() : array{
		$server = Server::getInstance();
		$player_instance = $server->getPlayerExact($this->spying);

		if($player_instance !== null){
			return $player_instance->getEnderChestInventory()->getContents();
		}

		$contents = [];
		foreach($server->getOfflinePlayerData($this->spying)->getListTag("EnderChestInventory") as $nbt){
			$contents[$nbt->getByte("Slot")] = Item::nbtDeserialize($nbt);
		}

		return $contents;
	}
}