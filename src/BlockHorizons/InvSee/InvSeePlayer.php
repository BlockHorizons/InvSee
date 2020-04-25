<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\listeners\InvSeeArmorInventoryListener;
use BlockHorizons\InvSee\listeners\InvSeeListeners;
use BlockHorizons\InvSee\listeners\PlayerArmorInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerEnderInventoryListener;
use BlockHorizons\InvSee\listeners\PlayerInventoryListener;
use BlockHorizons\InvSee\utils\InvCombiner;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\SharedInvMenu;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;

class InvSeePlayer{

	/** @var string */
	protected $player;

	/** @var SharedInvMenu */
	protected $inventory_menu;

	/** @var SharedInvMenu */
	protected $ender_inventory_menu;

	public function __construct(InventoryHandler $handler, string $player){
		$this->player = $player;
		$this->init($handler);
	}

	public function onJoin(Player $player) : void{
		$this->initPlayer($player);
	}

	public function onQuit(Player $player) : void{
		$this->destroyPlayer($player);
	}

	private function initPlayer(Player $player) : void{
		InvCombiner::split($this->inventory_menu->getInventory()->getContents(), $inventory, $armor_inventory);
		$player->getInventory()->setContents($inventory);
		$player->getArmorInventory()->setContents($armor_inventory);
		$player->getEnderChestInventory()->setContents($this->ender_inventory_menu->getInventory()->getContents());

		$player->getInventory()->addListeners(new PlayerInventoryListener($this->inventory_menu->getInventory()));
		$player->getArmorInventory()->addListeners(new PlayerArmorInventoryListener($this->inventory_menu->getInventory()));
		$player->getEnderChestInventory()->addListeners(new PlayerEnderInventoryListener($this->ender_inventory_menu->getInventory()));

		$this->inventory_menu->getInventory()->addListeners(
			new PlayerInventoryListener($player->getInventory()),
			new InvSeeArmorInventoryListener($player->getArmorInventory())
		);
		$this->ender_inventory_menu->getInventory()->addListeners(new PlayerEnderInventoryListener($player->getEnderChestInventory()));
	}

	private function destroyPlayer(Player $player) : void{
		foreach([
			$player->getInventory(),
			$player->getArmorInventory(),
			$player->getEnderChestInventory(),
			$this->inventory_menu->getInventory(),
			$this->ender_inventory_menu->getInventory()
		] as $inventory){
			$inventory->removeListeners(...InvSeeListeners::find($inventory->getListeners()));
		}
	}

	private function init(InventoryHandler $handler) : void{
		$this->inventory_menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$this->inventory_menu->setName($this->player . "'s Inventory");
		$this->inventory_menu->setListener(static function(Player $player, Item $in, Item $out, SlotChangeAction $action) : bool{
			$slot = $action->getSlot();
			return $slot < 36 || isset(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot]);
		});

		$this->ender_inventory_menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$this->ender_inventory_menu->setName($this->player . "'s Ender Inventory");

		$server = Server::getInstance();
		$player = $server->getPlayerExact($this->player);
		if($player !== null){
			$inventory = $player->getInventory()->getContents();
			$ender_inventory = $player->getEnderChestInventory()->getContents();
			$armor_inventory = $player->getArmorInventory()->getContents();
		}else{
			$inventory = [];
			$ender_inventory = [];
			$armor_inventory = [];

			$nbt = $server->getOfflinePlayerData($this->player);
			if($nbt === null){
				throw new \InvalidArgumentException("Could not find player data of \"" . $this->player . "\"");
			}

			$inventoryTag = $nbt->getListTag("Inventory");
			if($inventoryTag !== null){
				/** @var CompoundTag $item */
				foreach($inventoryTag as $i => $item){
					$slot = $item->getByte("Slot");
					if($slot >= 0 && $slot < 9){
						// old hotbar stuff
					}elseif($slot >= 100 && $slot < 104){
						$armor_inventory[$slot - 100] = Item::nbtDeserialize($item);
					}else{
						$inventory[$slot - 9] = Item::nbtDeserialize($item);
					}
				}

				$enderChestInventoryTag = $nbt->getListTag("EnderChestInventory");
				if($enderChestInventoryTag !== null){
					/** @var CompoundTag $item */
					foreach($enderChestInventoryTag as $i => $item){
						$ender_inventory[$item->getByte("Slot")] = Item::nbtDeserialize($item);
					}
				}
			}
		}

		$this->inventory_menu->getInventory()->setContents(InvCombiner::combine($inventory, $armor_inventory));
		$this->ender_inventory_menu->getInventory()->setContents($ender_inventory);

		$this->inventory_menu->setInventoryCloseListener(function() use($handler) : void{
			$handler->tryGarbageCollecting($this->player);
		});

		$this->ender_inventory_menu->setInventoryCloseListener(function() use($handler) : void{
			$handler->tryGarbageCollecting($this->player);
		});

		if($player !== null){
			$this->initPlayer($player);
		}
	}

	public function destroy() : void{
		$server = Server::getInstance();
		$player = $server->getPlayerExact($this->player);
		if($player === null){
			InvCombiner::split($this->inventory_menu->getInventory()->getContents(), $inventory, $armor_inventory);

			$serialized_inventory = [];
			foreach($inventory as $slot => $item){
				$serialized_inventory[] = $item->nbtSerialize($slot + 9);
			}

			foreach($armor_inventory as $slot => $item){
				$serialized_inventory[] = $item->nbtSerialize($slot + 100);
			}

			$serialized_ender_inventory = [];
			foreach($this->ender_inventory_menu->getInventory()->getContents() as $slot => $item){
				$serialized_ender_inventory[] = $item->nbtSerialize($slot);
			}

			$nbt = $server->getOfflinePlayerData($this->player);
			if($nbt === null){
				throw new RuntimeException("Failed to save player data - could not fetch player data");
			}

			$nbt->setTag("Inventory", new ListTag($serialized_inventory));
			$nbt->setTag("EnderChestInventory", new ListTag($serialized_ender_inventory));
			$server->saveOfflinePlayerData($this->player, $nbt);
		}else{
			$this->destroyPlayer($player);
		}
	}

	public function getInventoryMenu() : SharedInvMenu{
		return $this->inventory_menu;
	}

	public function getEnderChestInventoryMenu() : SharedInvMenu{
		return $this->ender_inventory_menu;
	}
}