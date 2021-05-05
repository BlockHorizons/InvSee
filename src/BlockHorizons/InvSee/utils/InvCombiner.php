<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils;

use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
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

	/**
	 * @param array<int, Item> $inventory
	 * @param array<int, Item> $armor
	 * @return array<int, Item>
	 */
	public static function combine(array $inventory, array $armor) : array{
		foreach($armor as $slot => $item){
			$inventory[self::ARMOR_TO_MENU_SLOTS[$slot]] = $item;
		}

		self::decorate($inventory);
		return $inventory;
	}

	/**
	 * @param array<int, Item> $inventory
	 * @param array<int, Item>|null $main
	 * @param array<int, Item>|null $armor
	 */
	public static function split(array $inventory, ?array &$main, ?array &$armor) : void{
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
	}

	/**
	 * @param array<int, Item> $inventory
	 */
	private static function decorate(array &$inventory) : void{
		$glass_pane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK());
		$inventory[45] = $inventory[53] = $glass_pane->asItem()->setCustomName("");
		$inventory[46] = $glass_pane->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "Helmet ->");
		$inventory[49] = $glass_pane->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "<- Chestplate | Leggings ->");
		$inventory[52] = $glass_pane->asItem()->setCustomName(TextFormat::RESET . TextFormat::AQUA . "<- Boots");
	}
}