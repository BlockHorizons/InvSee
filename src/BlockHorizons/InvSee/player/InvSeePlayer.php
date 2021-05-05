<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player;

use BlockHorizons\InvSee\player\handler\DestroyedInvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\InvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\NullInvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\OfflineInvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\OnlineInvSeePlayerHandler;
use BlockHorizons\InvSee\utils\InvCombiner;
use Logger;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;

final class InvSeePlayer{

	private static function destroyInvMenu(InvMenu $inv_menu) : void{
		foreach($inv_menu->getInventory()->getViewers() as $viewer){
			if($viewer->isConnected()){
				$viewer->removeCurrentWindow();
			}
		}
		$inv_menu->setListener(null);
		$inv_menu->setInventoryCloseListener(null);
	}

	protected string $player;
	protected Logger $logger;
	private InvSeePlayerHandler $handler;
	protected InvMenu $inventory_menu;
	protected InvMenu $ender_inventory_menu;

	public function __construct(string $player, Logger $logger){
		$this->player = $player;
		$this->logger = $logger;
		$this->handler = NullInvSeePlayerHandler::instance();
		$this->inventory_menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$this->ender_inventory_menu = InvMenu::create(InvMenu::TYPE_CHEST);
	}

	public function getPlayer() : string{
		return $this->player;
	}

	public function getInventoryMenu() : InvMenu{
		return $this->inventory_menu;
	}

	public function getEnderChestInventoryMenu() : InvMenu{
		return $this->ender_inventory_menu;
	}

	public function getLogger() : Logger{
		return $this->logger;
	}

	private function setHandler(InvSeePlayerHandler $handler) : void{
		if($this->handler !== $handler){
			$this->handler->destroy($this);
			$this->handler = $handler;
			$this->handler->init($this);
		}
	}

	/**
	 * @param Player $player
	 * @internal
	 */
	public function onPlayerOnline(Player $player) : void{
		$this->setHandler(new OnlineInvSeePlayerHandler($player));
	}

	/**
	 * @internal
	 */
	public function onPlayerOffline() : void{
		$this->setHandler(new OfflineInvSeePlayerHandler());
	}

	/**
	 * @param InvSeePlayerList $list
	 * @internal initialization is handled by {@see InvSeePlayerList::create()}
	 */
	public function init(InvSeePlayerList $list) : void{
		$this->inventory_menu->setName($this->player . "'s Inventory");
		$this->inventory_menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
			$player = $transaction->getPlayer();
			$permission = strtolower($player->getName()) === strtolower($this->player) ? "invsee.inventory.modify.self" : "invsee.inventory.modify";
			$slot = $transaction->getAction()->getSlot();
			return ($slot < 36 || isset(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot])) && $player->hasPermission($permission) ? $transaction->continue() : $transaction->discard();
		});
		$this->inventory_menu->setInventoryCloseListener(function() use($list) : void{
			$list->tryGarbageCollecting($this->player);
		});

		$this->ender_inventory_menu->setName($this->player . "'s Ender Inventory");
		$this->ender_inventory_menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
			$player = $transaction->getPlayer();
			return $player->hasPermission(strtolower($player->getName()) === strtolower($this->player) ? "invsee.enderinventory.modify.self" : "invsee.enderinventory.modify") ? $transaction->continue() : $transaction->discard();
		});
		$this->ender_inventory_menu->setInventoryCloseListener(function() use($list) : void{
			$list->tryGarbageCollecting($this->player);
		});

		$player = $list->getOnlinePlayer($this->player);
		if($player !== null){
			$inventory = $player->getInventory()->getContents();
			$ender_inventory = $player->getEnderInventory()->getContents();
			$armor_inventory = $player->getArmorInventory()->getContents();
		}else{
			$inventory = [];
			$ender_inventory = [];
			$armor_inventory = [];

			$nbt = Server::getInstance()->getOfflinePlayerData($this->player);
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

		if($player !== null){
			$this->onPlayerOnline($player);
		}else{
			$this->onPlayerOffline();
		}
	}

	/**
	 * @internal use {@see InvSeePlayerList::destroy()} instead
	 */
	public function destroy() : void{
		$this->setHandler(DestroyedInvSeePlayerHandler::instance());
		self::destroyInvMenu($this->inventory_menu);
		self::destroyInvMenu($this->ender_inventory_menu);
	}
}