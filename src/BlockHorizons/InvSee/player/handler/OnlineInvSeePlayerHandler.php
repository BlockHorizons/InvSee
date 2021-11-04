<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player\handler;

use BlockHorizons\InvSee\listeners\InvSeeArmorInventoryListener;
use BlockHorizons\InvSee\listeners\InvSeeListeners;
use BlockHorizons\InvSee\listeners\PlayerArmorInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerEnderInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerInventoryListener;
use BlockHorizons\InvSee\player\InvSeePlayer;
use BlockHorizons\InvSee\utils\InvCombiner;
use LogicException;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

final class OnlineInvSeePlayerHandler implements InvSeePlayerHandler{

	private ?Player $player;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function init(InvSeePlayer $player) : void{
		if($this->player === null){
			throw new LogicException("Tried to initialize in an un-constructed or an already-destroyed state");
		}

		$player->getLogger()->debug("Initializing online player instance: {$this->player->getName()}");

		// synchronize with existing InvSeePlayer and install listeners for real-time InvSeePlayer <-> Player synchronization

		InvCombiner::split($player->getInventoryMenu()->getInventory()->getContents(), $inventory, $armor_inventory);
		$this->player->getInventory()->setContents($inventory);
		$this->player->getArmorInventory()->setContents($armor_inventory);
		$this->player->getEnderInventory()->setContents($player->getEnderChestInventoryMenu()->getInventory()->getContents());

		$this->player->getInventory()->getListeners()->add(new PlayerInventoryListener($player->getInventoryMenu()->getInventory()));
		$this->player->getArmorInventory()->getListeners()->add(new PlayerArmorInventoryListener($player->getInventoryMenu()->getInventory()));
		$this->player->getEnderInventory()->getListeners()->add(new PlayerEnderInventoryListener($player->getEnderChestInventoryMenu()->getInventory()));

		$player->getInventoryMenu()->getInventory()->getListeners()->add(
			new PlayerInventoryListener($this->player->getInventory()),
			new InvSeeArmorInventoryListener($this->player->getArmorInventory())
		);
		$player->getEnderChestInventoryMenu()->getInventory()->getListeners()->add(new PlayerEnderInventoryListener($this->player->getEnderInventory()));
	}

	public function destroy(InvSeePlayer $player) : void{
		if($this->player === null){
			throw new LogicException("Tried to destroy in an un-constructed or an already-destroyed state");
		}

		$player->getLogger()->debug("Destroying online player instance: {$this->player->getName()}");

		/** @var Inventory $inventory */
		foreach([
			$this->player->getInventory(),
			$this->player->getArmorInventory(),
			$this->player->getEnderInventory(),
			$player->getInventoryMenu()->getInventory(),
			$player->getEnderChestInventoryMenu()->getInventory()
		] as $inventory){
			$inventory->getListeners()->remove(...InvSeeListeners::find($inventory->getListeners()->toArray()));
		}

		$this->player = null;

		// no need to save inventory data, inventory listeners synchronized with online inventory in real-time
	}
}