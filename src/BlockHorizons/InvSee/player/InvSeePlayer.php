<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player;

use BlockHorizons\InvSee\player\handler\DestroyedInvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\InvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\NullInvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\OfflineInvSeePlayerHandler;
use BlockHorizons\InvSee\player\handler\OnlineInvSeePlayerHandler;
use BlockHorizons\InvSee\utils\InvCombiner;
use BlockHorizons\InvSee\utils\OfflinePlayerInventory;
use InvalidArgumentException;
use Logger;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
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

	private InvSeePlayerHandler $handler;
	readonly public InvMenu $inventory_menu;
	readonly public InvMenu $ender_inventory_menu;

	public function __construct(
		readonly public string $player,
		readonly public Logger $logger
	){
		$this->handler = NullInvSeePlayerHandler::instance();
		$this->inventory_menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
		$this->ender_inventory_menu = InvMenu::create(InvMenu::TYPE_CHEST);
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
			return ($slot < 36 || isset(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot]) || $slot === InvCombiner::OFFHAND_SLOT_OFFSET) && $player->hasPermission($permission) ? $transaction->continue() : $transaction->discard();
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
			$offhand_inventory = $player->getOffHandInventory()->getContents();
		}else{
			$nbt = Server::getInstance()->getOfflinePlayerData($this->player) ?? throw new InvalidArgumentException("Could not find player data of \"" . $this->player . "\"");
			$offline_player_inventory = new OfflinePlayerInventory($nbt);
			$inventory = $offline_player_inventory->readInventory();
			$ender_inventory = $offline_player_inventory->readEnderInventory();
			$armor_inventory = $offline_player_inventory->readArmorInventory();
			$offhand_inventory = [$offline_player_inventory->readOffhandItem()];
		}

		$this->inventory_menu->getInventory()->setContents(InvCombiner::combine($inventory, $armor_inventory, $offhand_inventory));
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