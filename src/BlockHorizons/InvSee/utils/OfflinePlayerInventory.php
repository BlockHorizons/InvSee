<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils;

use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

final class OfflinePlayerInventory{

	public static function fromOfflinePlayerData(CompoundTag $data) : self{
		return new self($data);
	}

	private CompoundTag $data;

	public function __construct(CompoundTag $data){
		$this->data = $data;
	}

	public function getOfflinePlayerData() : CompoundTag{
		return $this->data;
	}

	/**
	 * @param Item[] $inventory
	 * @param Item[] $armor_inventory
	 *
	 * @phpstan-param array<int, Item> $inventory
	 * @phpstan-param array<int, Item> $armor_inventory
	 */
	private function readInventoryAndArmorInventory(array &$inventory, array &$armor_inventory) : void{
		$inventory = [];
		$armor_inventory = [];

		$tag = $this->data->getListTag("Inventory");
		if($tag === null){
			return;
		}

		/** @var CompoundTag $item */
		foreach($tag->getIterator() as $i => $item){
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
	 * @param Item[] $inventory
	 * @param Item[] $armor_inventory
	 *
	 * @phpstan-param array<int, Item> $inventory
	 * @phpstan-param array<int, Item> $armor_inventory
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
	 * @return Item[]
	 *
	 * @phpstan-return array<int, Item>
	 */
	public function readInventory() : array{
		$inventory = [];
		$_ = [];
		$this->readInventoryAndArmorInventory($inventory, $_);
		return $inventory;
	}

	/**
	 * @param Item[] $inventory
	 * @return self
	 *
	 * @phpstan-param array<int, Item> $inventory
	 */
	public function writeInventory(array $inventory) : self{
		$_ = [];
		$armor_inventory = [];
		$this->readInventoryAndArmorInventory($_, $armor_inventory);
		$this->writeInventoryAndArmorInventory($inventory, $armor_inventory);
		return $this;
	}

	/**
	 * @return Item[]
	 *
	 * @phpstan-return array<int, Item>
	 */
	public function readArmorInventory() : array{
		$_ = [];
		$inventory = [];
		$this->readInventoryAndArmorInventory($_, $inventory);
		return $inventory;
	}

	/**
	 * @param Item[] $inventory
	 * @return self
	 *
	 * @phpstan-param array<int, Item> $inventory
	 */
	public function writeArmorInventory(array $inventory) : self{
		$normal_inventory = [];
		$_ = [];
		$this->readInventoryAndArmorInventory($normal_inventory, $_);
		$this->writeInventoryAndArmorInventory($normal_inventory, $inventory);
		return $this;
	}

	/**
	 * @return Item[]
	 *
	 * @phpstan-return array<int, Item>
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
	 * @param Item[] $inventory
	 * @return self
	 *
	 * @phpstan-param array<int, Item> $inventory
	 */
	public function writeEnderInventory(array $inventory) : self{
		$tag = new ListTag([], NBT::TAG_Compound);
		foreach($inventory as $slot => $item){
			$tag->push($item->nbtSerialize($slot));
		}
		$this->data->setTag("EnderChestInventory", $tag);
		return $this;
	}
}