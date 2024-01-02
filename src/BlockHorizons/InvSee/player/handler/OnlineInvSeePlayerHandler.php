<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player\handler;

use BlockHorizons\InvSee\listeners\InvSeeArmorInventoryListener;
use BlockHorizons\InvSee\listeners\InvSeeListeners;
use BlockHorizons\InvSee\listeners\InvSeeOffhandInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerArmorInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerEnderInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerOffhandInventoryListener;
use BlockHorizons\InvSee\player\InvSeePlayer;
use BlockHorizons\InvSee\utils\InvCombiner;
use LogicException;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

final class OnlineInvSeePlayerHandler implements InvSeePlayerHandler{

	public function __construct(
		private ?Player $player
	){}

	public function init(InvSeePlayer $player) : void{
		$this->player ??= throw new LogicException("Tried to initialize in an un-constructed or an already-destroyed state");

		$player->logger->debug("Initializing online player instance: {$this->player->getName()}");

		// synchronize with existing InvSeePlayer and install listeners for real-time InvSeePlayer <-> Player synchronization

		InvCombiner::split($player->inventory_menu->getInventory()->getContents(), $inventory, $armor_inventory, $offhand_item);
		$this->player->getInventory()->setContents($inventory);
		$this->player->getArmorInventory()->setContents($armor_inventory);
		$this->player->getEnderInventory()->setContents($player->ender_inventory_menu->getInventory()->getContents());
		$this->player->getOffHandInventory()->setContents([$offhand_item]);

		$this->player->getInventory()->getListeners()->add(new PlayerInventoryListener($player->inventory_menu->getInventory()));
		$this->player->getArmorInventory()->getListeners()->add(new PlayerArmorInventoryListener($player->inventory_menu->getInventory()));
		$this->player->getEnderInventory()->getListeners()->add(new PlayerEnderInventoryListener($player->ender_inventory_menu->getInventory()));
		$this->player->getOffHandInventory()->getListeners()->add(new PlayerOffhandInventoryListener($player->inventory_menu->getInventory()));

		$player->inventory_menu->getInventory()->getListeners()->add(
			new PlayerInventoryListener($this->player->getInventory()),
			new InvSeeArmorInventoryListener($this->player->getArmorInventory()),
			new InvSeeOffhandInventoryListener($this->player->getOffHandInventory())
		);
		$player->ender_inventory_menu->getInventory()->getListeners()->add(new PlayerEnderInventoryListener($this->player->getEnderInventory()));
	}

	public function destroy(InvSeePlayer $player) : void{
		$this->player ??= throw new LogicException("Tried to destroy in an un-constructed or an already-destroyed state");
		$player->logger->debug("Destroying online player instance: {$this->player->getName()}");

		/** @var Inventory $inventory */
		foreach([
			$this->player->getInventory(),
			$this->player->getArmorInventory(),
			$this->player->getEnderInventory(),
			$this->player->getOffHandInventory(),
			$player->inventory_menu->getInventory(),
			$player->ender_inventory_menu->getInventory()
		] as $inventory){
			$inventory->getListeners()->remove(...InvSeeListeners::find($inventory->getListeners()->toArray()));
		}

		$this->player = null;

		// no need to save inventory data, inventory listeners synchronized with online inventory in real-time
	}
}