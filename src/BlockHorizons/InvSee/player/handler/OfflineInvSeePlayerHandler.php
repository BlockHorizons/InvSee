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
		$player->logger->debug("Saving offline inventory data");
		InvCombiner::split($player->inventory_menu->getInventory()->getContents(), $inventory, $armor_inventory, $offhand_inventory);

		$server = Server::getInstance();
		$nbt = $server->getOfflinePlayerData($player->player) ?? throw new RuntimeException("Failed to save player data - could not fetch player data");
		$server->saveOfflinePlayerData($player->player, (new OfflinePlayerInventory($nbt))
			->writeInventory($inventory)
			->writeArmorInventory($armor_inventory)
			->writeEnderInventory($player->ender_inventory_menu->getInventory()->getContents())
			->writeOffhandItem($offhand_inventory)
		->data);
	}
}