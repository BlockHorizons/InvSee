<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player\handler;

use BlockHorizons\InvSee\player\InvSeePlayer;
use BlockHorizons\InvSee\utils\InvCombiner;
use BlockHorizons\InvSee\utils\OfflinePlayerInventory;
use pocketmine\Server;
use RuntimeException;

final class OfflineInvSeePlayerHandler implements InvSeePlayerHandler{

	public function init(InvSeePlayer $player) : void{
	}

	public function destroy(InvSeePlayer $player) : void{
		$player->getLogger()->debug("Saving offline inventory data");
		InvCombiner::split($player->getInventoryMenu()->getInventory()->getContents(), $inventory, $armor_inventory, $offhand_inventory);

		$server = Server::getInstance();
		$nbt = $server->getOfflinePlayerData($player->getPlayer()) ?? throw new RuntimeException("Failed to save player data - could not fetch player data");
		$server->saveOfflinePlayerData($player->getPlayer(), OfflinePlayerInventory::fromOfflinePlayerData($nbt)
			->writeInventory($inventory)
			->writeArmorInventory($armor_inventory)
			->writeEnderInventory($player->getEnderChestInventoryMenu()->getInventory()->getContents())
			->writeOffhandItem($offhand_inventory)
		->getOfflinePlayerData());
	}
}