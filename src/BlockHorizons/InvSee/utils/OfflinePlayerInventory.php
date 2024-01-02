<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Server;

/**
 * Manages inventory data of offline players by interfacing with server's data storage system to read and modify
 * inventory data for players who are not currently online. The class provides methods to interact with the following
 * types of inventories associated with a player:
 * - Main inventory
 * - Armor inventory
 * - Ender chest inventory
 * - Off-hand inventory
 *
 * Example usage:
 * $player = "steve"; // offline player's name
 * $data = Server::getInstance()->getOfflinePlayerData($player); // read data
 * $offline_inv = new OfflinePlayerInventory($data);
 * $inventory_contents = $offline_inv->readInventory(); // read player inventory
 * $inventory_contents[0] = VanillaItems::DIAMOND(); // set diamond in player inventory (slot 0)
 * $offline_inv->writeInventory($inventory_contents); // update inventory contents
 * Server::getInstance()->saveOfflinePlayerData($player, $offline_inv->data); // write updated data
 */
final class OfflinePlayerInventory{

	public function __construct(
		readonly public CompoundTag $data
	){}

	/**
	 * Reads main inventory and armor inventory contents from offline player data into given $inventory and
	 * $armor_inventory parameters.
	 *
	 * @param array<int, Item> $inventory
	 * @param array<int, Item> $armor_inventory
	 */
	private function readInventoryAndArmorInventory(array &$inventory, array &$armor_inventory) : void{
		$inventory = [];
		$armor_inventory = [];

		$tag = $this->data->getListTag("Inventory");
		if($tag === null){
			return;
		}

		/** @var CompoundTag $item */
		foreach($tag->getIterator() as $item){
			$slot = $item->getByte("Slot");
			if($slot >= 0 && $slot < 9){
				// old hotbar stuff
			}elseif($slot >= 100 && $slot < 104){
				$armor_inventory[$slot - 100] = Item::nbtDeserialize($item);
			}else{
				$inventory[$slot - 9] = Item::nbtDeserialize($item);
			}
		}
	}

	/**
	 * Writes given main inventory and armor inventory contents to offline player data.
	 *
	 * @param array<int, Item> $inventory
	 * @param array<int, Item> $armor_inventory
	 */
	private function writeInventoryAndArmorInventory(array $inventory, array $armor_inventory) : void{
		$serialized_inventory = [];
		foreach($inventory as $slot => $item){
			$serialized_inventory[] = $item->nbtSerialize($slot + 9);
		}

		foreach($armor_inventory as $slot => $item){
			$serialized_inventory[] = $item->nbtSerialize($slot + 100);
		}

		$this->data->setTag("Inventory", new ListTag($serialized_inventory, NBT::TAG_Compound));
	}

	/**
	 * Reads main inventory contents from offline player data. The return value is equivalent to executing
	 * $player->getInventory()->getContents() on the player had they been online. Execute
	 * {@see Server::saveOfflinePlayerData()} passing {@see OfflinePlayerInventory::$data} to write changes to disk.
	 *
	 * @return array<int, Item>
	 */
	public function readInventory() : array{
		$inventory = [];
		$_ = [];
		$this->readInventoryAndArmorInventory($inventory, $_);
		return $inventory;
	}

	/**
	 * Writes main inventory contents to offline player data. This operation is equivalent to executing
	 * $player->getInventory()->setContents($inventory) on the player had they been online.
	 *
	 * @param array<int, Item> $inventory
	 * @return self
	 */
	public function writeInventory(array $inventory) : self{
		$_ = [];
		$armor_inventory = [];
		$this->readInventoryAndArmorInventory($_, $armor_inventory);
		$this->writeInventoryAndArmorInventory($inventory, $armor_inventory);
		return $this;
	}

	/**
	 * Reads armor inventory contents from offline player data. The return value is equivalent to executing
	 * $player->getArmorInventory()->getContents() on the player had they been online. Execute
	 * {@see Server::saveOfflinePlayerData()} passing {@see OfflinePlayerInventory::$data} to write changes to disk.
	 *
	 * @return array<int, Item>
	 */
	public function readArmorInventory() : array{
		$_ = [];
		$inventory = [];
		$this->readInventoryAndArmorInventory($_, $inventory);
		return $inventory;
	}

	/**
	 * Writes armor inventory contents to offline player data. This operation is equivalent to executing
	 * $player->getArmorInventory()->setContents($inventory) on the player had they been online. Execute
	 * {@see Server::saveOfflinePlayerData()} passing {@see OfflinePlayerInventory::$data} to write changes to disk.
	 *
	 * @param array<int, Item> $inventory
	 * @return self
	 */
	public function writeArmorInventory(array $inventory) : self{
		$normal_inventory = [];
		$_ = [];
		$this->readInventoryAndArmorInventory($normal_inventory, $_);
		$this->writeInventoryAndArmorInventory($normal_inventory, $inventory);
		return $this;
	}

	/**
	 * Reads ender inventory contents from offline player data. The return value is equivalent to executing
	 * $player->getEnderInventory()->getContents() on the player had they been online.
	 *
	 * @return array<int, Item>
	 */
	public function readEnderInventory() : array{
		$enderChestInventoryTag = $this->data->getListTag("EnderChestInventory");
		if($enderChestInventoryTag === null){
			return [];
		}

		$ender_inventory = [];
		/** @var CompoundTag $item */
		foreach($enderChestInventoryTag->getIterator() as $i => $item){
			$ender_inventory[$item->getByte("Slot")] = Item::nbtDeserialize($item);
		}
		return $ender_inventory;
	}

	/**
	 * Writes ender inventory contents to offline player data. This operation is equivalent to executing
	 * $player->getEnderInventory()->setContents($inventory) on the player had they been online. Execute
	 * {@see Server::saveOfflinePlayerData()} passing {@see OfflinePlayerInventory::$data} to write changes to disk.
	 *
	 * @param array<int, Item> $inventory
	 * @return self
	 */
	public function writeEnderInventory(array $inventory) : self{
		$tag = new ListTag([], NBT::TAG_Compound);
		foreach($inventory as $slot => $item){
			$tag->push($item->nbtSerialize($slot));
		}
		$this->data->setTag("EnderChestInventory", $tag);
		return $this;
	}

	/**
	 * Reads offhand item from offline player data. The return value is equivalent to executing
	 * $player->getOffHandInventory()->getItem(0) on the player had they been online.
	 *
	 * @return Item
	 */
	public function readOffhandItem() : Item{
		$offHand = $this->data->getCompoundTag("OffHandItem");
		return $offHand !== null ? Item::nbtDeserialize($offHand) : VanillaItems::AIR();
	}

	/**
	 * Writes offhand item to offline player data. This operation is equivalent to executing
	 * $player->getOffHandInventory()->setItem(0, $item) on the player had they been online. Execute
	 * {@see Server::saveOfflinePlayerData()} passing {@see OfflinePlayerInventory::$data} to write changes to disk.
	 *
	 * @param Item $item
	 * @return self
	 */
	public function writeOffhandItem(Item $item) : self{
		if($item->isNull()){
			$this->data->removeTag("OffHandItem");
		}else{
			$this->data->setTag("OffHandItem", $item->nbtSerialize());
		}
		return $this;
	}
}
