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
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\Player;
use pocketmine\Server;
use RuntimeException;

class InvSeePlayer{

	private static function destroyInvMenu(InvMenu $inv_menu) : void{
		foreach($inv_menu->getInventory()->getViewers() as $viewer){
			if($viewer->isConnected()){
				$viewer->removeCurrentWindow();
			}
		}
		$inv_menu->setListener(null);
		$inv_menu->setInventoryCloseListener(null);
	}

	/** @var string */
	protected $player;

	/** @var InvMenu|null */
	protected $inventory_menu;

	/** @var InvMenu|null */
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
		assert($this->inventory_menu !== null);
		assert($this->ender_inventory_menu !== null);

		InvCombiner::split($this->inventory_menu->getInventory()->getContents(), $inventory, $armor_inventory);
		$player->getInventory()->setContents($inventory);
		$player->getArmorInventory()->setContents($armor_inventory);
		$player->getEnderChestInventory()->setContents($this->ender_inventory_menu->getInventory()->getContents());

		$player->getInventory()->getListeners()->add(new PlayerInventoryListener($this->inventory_menu->getInventory()));
		$player->getArmorInventory()->getListeners()->add(new PlayerArmorInventoryListener($this->inventory_menu->getInventory()));
		$player->getEnderChestInventory()->getListeners()->add(new PlayerEnderInventoryListener($this->ender_inventory_menu->getInventory()));

		$this->inventory_menu->getInventory()->getListeners()->add(
			new PlayerInventoryListener($player->getInventory()),
			new InvSeeArmorInventoryListener($player->getArmorInventory())
		);
		$this->ender_inventory_menu->getInventory()->getListeners()->add(new PlayerEnderInventoryListener($player->getEnderChestInventory()));
	}

	private function destroyPlayer(Player $player) : void{
		assert($this->inventory_menu !== null);
		assert($this->ender_inventory_menu !== null);
		/** @var Inventory $inventory */
		foreach([
			$player->getInventory(),
			$player->getArmorInventory(),
			$player->getEnderChestInventory(),
			$this->inventory_menu->getInventory(),
			$this->ender_inventory_menu->getInventory()
		] as $inventory){
			$inventory->getListeners()->remove(...InvSeeListeners::find($inventory->getListeners()->toArray()));
		}
	}

	private function init(InventoryHandler $handler) : void{
		assert($this->inventory_menu !== null);
		assert($this->ender_inventory_menu !== null);
		$this->inventory_menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$this->inventory_menu->setName($this->player . "'s Inventory");
		$this->inventory_menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
			$player = $transaction->getPlayer();
			$permission = strtolower($player->getName()) === strtolower($this->player) ? "invsee.inventory.modify.self" : "invsee.inventory.modify";
			$slot = $transaction->getAction()->getSlot();
			return ($slot < 36 || isset(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot])) && $player->hasPermission($permission) ? $transaction->continue() : $transaction->discard();
		});

		$this->ender_inventory_menu = InvMenu::create(InvMenu::TYPE_CHEST);
		$this->ender_inventory_menu->setName($this->player . "'s Ender Inventory");
		$this->ender_inventory_menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
			$player = $transaction->getPlayer();
			return $player->hasPermission(strtolower($player->getName()) === strtolower($this->player) ? "invsee.enderinventory.modify.self" : "invsee.enderinventory.modify") ? $transaction->continue() : $transaction->discard();
		});

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
				foreach($inventoryTag->getIterator() as $i => $item){
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
					foreach($enderChestInventoryTag->getIterator() as $i => $item){
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
		assert($this->inventory_menu !== null);
		assert($this->ender_inventory_menu !== null);
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

		self::destroyInvMenu($this->inventory_menu);
		$this->inventory_menu = null;

		self::destroyInvMenu($this->ender_inventory_menu);
		$this->ender_inventory_menu = null;
	}

	public function getInventoryMenu() : InvMenu{
		return $this->inventory_menu;
	}

	public function getEnderChestInventoryMenu() : InvMenu{
		return $this->ender_inventory_menu;
	}
}