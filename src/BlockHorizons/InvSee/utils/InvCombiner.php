<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

final class InvCombiner{

	public const ARMOR_TO_MENU_SLOTS = [
		0 => 47,
		1 => 48,
		2 => 50,
		3 => 51
	];

	public const MENU_TO_ARMOR_SLOTS = [
		47 => 0,
		48 => 1,
		50 => 2,
		51 => 3
	];

	public const OFFHAND_SLOT_OFFSET = 53;

	/**
	 * @param array<int, Item> $inventory
	 * @param array<int, Item> $armor
	 * @param array<int, Item> $offhand
	 * @return array<int, Item>
	 */
	public static function combine(array $inventory, array $armor, array $offhand) : array{
		foreach($armor as $slot => $item){
			$inventory[self::ARMOR_TO_MENU_SLOTS[$slot]] = $item;
		}

		foreach($offhand as $slot => $item){
			$inventory[self::OFFHAND_SLOT_OFFSET + $slot] = $item;
		}
		self::decorate($inventory);
		return $inventory;
	}

	/**
	 * @param array<int, Item> $inventory
	 * @param array<int, Item>|null $main
	 * @param array<int, Item>|null $armor
	 * @param Item|null $offhand_inventory
	 */
	public static function split(array $inventory, ?array &$main, ?array &$armor, ?Item &$offhand_inventory) : void{
		$main = [];
		for($i = 0; $i < 36; ++$i){
			if(isset($inventory[$i])){
				$main[$i] = $inventory[$i];
			}
		}

		$armor = [];
		foreach(self::ARMOR_TO_MENU_SLOTS as $armor_slot => $menu_slot){
			if(isset($inventory[$menu_slot])){
				$armor[$armor_slot] = $inventory[$menu_slot];
			}
		}

		$offhand_inventory = $inventory[self::OFFHAND_SLOT_OFFSET] ?? VanillaItems::AIR();
	}

	/**
	 * @param array<int, Item> $inventory
	 */
	private static function decorate(array &$inventory) : void{
		$glass_pane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK);
		$inventory[45] = $glass_pane->asItem()->setCustomName(" ");
		$inventory[46] = $glass_pane->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "Helmet ->");
		$inventory[49] = $glass_pane->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "<- Chestplate | Leggings ->");
		$inventory[52] = $glass_pane->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "<- Boots | Offhand ->");
	}
}