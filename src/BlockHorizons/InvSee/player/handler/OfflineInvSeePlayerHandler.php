<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player\handler;

use BlockHorizons\InvSee\player\InvSeePlayer;
use BlockHorizons\InvSee\utils\InvCombiner;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Server;
use RuntimeException;

final class OfflineInvSeePlayerHandler implements InvSeePlayerHandler{

	public function init(InvSeePlayer $player) : void{
	}

	public function destroy(InvSeePlayer $player) : void{
		$player->getLogger()->debug("Saving offline inventory data");
		InvCombiner::split($player->getInventoryMenu()->getInventory()->getContents(), $inventory, $armor_inventory);

		$serialized_inventory = [];
		foreach($inventory as $slot => $item){
			$serialized_inventory[] = $item->nbtSerialize($slot + 9);
		}

		foreach($armor_inventory as $slot => $item){
			$serialized_inventory[] = $item->nbtSerialize($slot + 100);
		}

		$serialized_ender_inventory = [];
		foreach($player->getEnderChestInventoryMenu()->getInventory()->getContents() as $slot => $item){
			$serialized_ender_inventory[] = $item->nbtSerialize($slot);
		}

		$server = Server::getInstance();
		$nbt = $server->getOfflinePlayerData($player->getPlayer());
		if($nbt === null){
			throw new RuntimeException("Failed to save player data - could not fetch player data");
		}

		$nbt->setTag("Inventory", new ListTag($serialized_inventory, NBT::TAG_Compound));
		$nbt->setTag("EnderChestInventory", new ListTag($serialized_ender_inventory, NBT::TAG_Compound));
		$server->saveOfflinePlayerData($player->getPlayer(), $nbt);
	}
}