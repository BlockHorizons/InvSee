<?php
namespace BlockHorizons\InvSee;

use muqsit\invmenu\inventories\BaseFakeInventory;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;

class InventoryHandler {

	const PLAYER_INVENTORY_SIZE = 36;

	const ARMOR_INVENTORY_OFFSET = 100;

	const ARMOR_INVENTORY_MAP = [ //[pmmp "Inventory" tag slot] => (InvSee's slot)
		self::ARMOR_INVENTORY_OFFSET => 47,
		self::ARMOR_INVENTORY_OFFSET + 1 => 48,
		self::ARMOR_INVENTORY_OFFSET + 2 => 50,
		self::ARMOR_INVENTORY_OFFSET + 3 => 51,
	];

	/** @var InvMenu[] */
	private $ender_menus = [];

	/** @var string[] */
	private $player_uuids = [];

	/** @var string[] */
	private $viewings = [];

	/** @var Server */
	private $server;

	public function __construct(Loader $loader) {
		if(!InvMenuHandler::isRegistered()) {
			InvMenuHandler::register($loader);
		}

		$this->server = $loader->getServer();
	}

	/**
	 * Enables real-time syncing of inventory transactions for
	 * a player's inventory.
	 *
	 * When enabled, the inventory changes will directly be set
	 * to the player's inventory rather than saving into
	 * players/player.dat file.
	 *
	 * @param Player $player
	 */
	public function enableSyncing(Player $player): void {
		$this->player_uuids[$username = $player->getLowerCaseName()] = $player->getRawUniqueId();

		if(isset($this->menus[$username])) {
			$player->getInventory()->setContents($this->menus[$username]->getInventory()->getContents());
		}

		if(isset($this->ender_menus[$username])) {
			$player->getEnderChestInventory()->setContents($this->ender_menus[$username]->getInventory()->getContents());
		}
	}

	/**
	 * Disables real-time syncing of inventory transactions for
	 * a player's inventory.
	 *
	 * When disabled, the inventory changes will directly be set
	 * to players/player.dat file rather than updating the
	 * player's inventory.
	 *
	 * @param Player $player
	 */
	public function disableSyncing(Player $player): void {
		unset($this->player_uuids[$player->getLowerCaseName()], $this->viewings[$player->getRawUniqueId()]);
	}

	/**
	 * Updates player's inventory instance with the inventory
	 * changes.
	 *
	 * @param Player $player
	 * @param Item $itemClicked
	 * @param Item $itemClickedWith
	 * @param SlotChangeAction $inventoryAction
	 */
	public function syncInventory(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $inventoryAction): bool {
		if(isset($this->viewings[$key = $player->getRawUniqueId()])) {
			if(!$player->hasPermission("invsee.inventory.modify")) {
				return false;
			}

			$slot = $inventoryAction->getSlot();
			if($slot >= self::PLAYER_INVENTORY_SIZE && ($slot = array_search($slot, self::ARMOR_INVENTORY_MAP, true)) === false) {
				return false;//invalid slot
			}

			$player = isset($this->player_uuids[$uuid = $this->viewings[$key]]) ? $this->server->getPlayerByRawUUID($this->player_uuids[$uuid]) : null;
			if($player === null) {
				return true;
			}

			if(isset(self::ARMOR_INVENTORY_MAP[$slot])) {
				return $player->getArmorInventory()->setItem($slot - 100, $inventoryAction->getTargetItem());
			}

			return $player->getInventory()->setItem($slot, $inventoryAction->getTargetItem());
		}

		return true;
	}

	/**
	 * Updates player's ender inventory instance with the
	 * ender inventory changes.
	 *
	 * @param Player $player
	 * @param Item $itemClicked
	 * @param Item $itemClickedWith
	 * @param SlotChangeAction $inventoryAction
	 */
	public function syncEnderInventory(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $inventoryAction): bool {
		if(isset($this->viewings[$key = $player->getRawUniqueId()])) {
			if(!$player->hasPermission("invsee.enderinventory.modify")) {
				return false;
			}

			$player = isset($this->player_uuids[$uuid = $this->viewings[$key]]) ? $this->server->getPlayerByRawUUID($this->player_uuids[$uuid]) : null;
			return $player === null || $player->getEnderChestInventory()->setItem($inventoryAction->getSlot(), $inventoryAction->getTargetItem());
		}
		return true;
	}

	/**
	 * Called when an inventory viewer stops viewing an
	 * inventory.
	 * InvSee will attempt to save inventory data to file
	 * if the player is offline.
	 *
	 * @param Player $player
	 * @param BaseFakeInventory $inventory
	 */
	public function onInvClose(Player $player, BaseFakeInventory $inventory): void {
		if(isset($this->viewings[$uuid = $player->getRawUniqueId()])) {
			$viewing = $this->viewings[$uuid];
			unset($this->viewings[$uuid]);

			if(empty($inventory->getViewers())) {
				unset($this->menus[$viewing]);

				if(!isset($this->player_uuids[$viewing])) {//player changed offline player's inventory
					$tag = new ListTag("Inventory");
					foreach($inventory->getContents() as $slot => $item) {
						if($slot >= self::PLAYER_INVENTORY_SIZE) {
							$slot = array_search($slot, self::ARMOR_INVENTORY_MAP, true);
							if($slot === false) {
								continue;
							}
						}

                        $tag->push($item->nbtSerialize($slot));
					}

					$nbt = $this->server->getOfflinePlayerData($viewing);
					$nbt->setTag($tag);
					$this->server->saveOfflinePlayerData($viewing, $nbt);
				}
			}
		}
	}

	/**
	 * Called when an inventory viewer stops viewing an
	 * ender inventory.
	 * InvSee will attempt to save inventory data to file
	 * if the player is offline.
	 *
	 * @param Player $player
	 * @param BaseFakeInventory $inventory
	 */
	public function onEnderInvClose(Player $player, BaseFakeInventory $inventory): void {
		if(isset($this->viewings[$uuid = $player->getRawUniqueId()])) {
			$viewing = $this->viewings[$uuid];
			unset($this->viewings[$uuid]);

			if(empty($inventory->getViewers())) {
				unset($this->ender_menus[$viewing]);

				if(!isset($this->player_uuids[$viewing])) {//player changed offline player's inventory
					$tag = new ListTag("EnderChestInventory");
					foreach($inventory->getContents() as $slot => $item) {
						$tag->push($item->nbtSerialize($slot));
					}

					$nbt = $this->server->getOfflinePlayerData($viewing);
					$nbt->setTag($tag);
					$this->server->saveOfflinePlayerData($viewing, $nbt);
				}
			}
		}
	}

	/**
	 * Opens a player's inventory to the viewer.
	 *
	 * @param Player $viewer
	 * @param string $player
	 *
	 * @return bool whether the inventory was sent to
	 * the viewer successfully.
	 */
	public function viewInventory(Player $viewer, string $player): bool {
		$player = strtolower($player);
		if($viewer->getLowerCaseName() === $player) { //prevents weird transaction issues when grouping separated items
			return false;
		}

		$this->viewings[$viewer->getRawUniqueId()] = $player;

		if(isset($this->menus[$player])) {
			$this->menus[$player]->send($viewer);
			return true;
		}

		$this->menus[$player] = $menu = $this->createInvMenu();

		$player_instance = $this->server->getPlayerExact($player);
		if($player_instance !== null) {
			$menu->setName($player_instance->getName() . "'s Inventory");

			$inventory = $menu->getInventory();
			foreach($player_instance->getInventory()->getContents() as $slot => $item) {
				$inventory->setItem($slot, $item, false);
			}

			foreach($player_instance->getArmorInventory()->getContents() as $slot => $item) {
				$inventory->setItem(self::ARMOR_INVENTORY_MAP[self::ARMOR_INVENTORY_OFFSET + $slot], $item, false);
			}
		} else {
			$menu->setName($player . "'s Inventory");
			if (is_file($this->server->getDataPath() . "players/" . $player . ".dat")) {
				$tag = $this->server->getOfflinePlayerData($player)->getListTag("Inventory");

				$inventory = $menu->getInventory();
				foreach($tag as $nbt) {
					$slot = $nbt->getByte("Slot");
					$inventory->setItem(self::ARMOR_INVENTORY_MAP[$slot] ?? $slot, Item::nbtDeserialize($nbt), false);
				}
			}
		}

		$menu->send($viewer);
		return true;
	}

	/**
	 * Opens a player's ender inventory to the
	 * viewer.
	 *
	 * @param Player $viewer
	 * @param string $player
	 *
	 * @return bool whether the inventory was sent to
	 * the viewer successfully.
	 */
	public function viewEnderInventory(Player $viewer, string $player): bool {
		$player = strtolower($player);
		$this->viewings[$viewer->getRawUniqueId()] = $player;

		if(isset($this->ender_menus[$player])) {
			$this->ender_menus[$player]->send($viewer);
			return true;
		}

		$this->ender_menus[$player] = $menu = $this->createEnderInvMenu();

		$player_instance = $this->server->getPlayerExact($player);
		if($player_instance !== null) {
			$menu->setName($player_instance->getName() . "'s Ender Inventory");
			$menu->getInventory()->setContents($player_instance->getEnderChestInventory()->getContents(), false);
		} else {
			$menu->setName($player . "'s Ender Inventory");
			if (is_file($this->server->getDataPath() . "players/" . $player . ".dat")) {
				$tag = $this->server->getOfflinePlayerData($player)->getListTag("EnderChestInventory");

				$items = [];
				foreach($tag as $nbt) {
					$items[$nbt->getByte("Slot")] = Item::nbtDeserialize($nbt);
				}
				$menu->getInventory()->setContents($items, false);
			}
		}

		$menu->send($viewer);
		return true;
	}

	private function createInvMenu(): InvMenu {
		$menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);

		$inventory = $menu->getInventory();
		$size = $inventory->getSize();

		$barrier_item = Item::get(Item::STAINED_GLASS_PANE, 15);
		$barrier_item->setCustomName(" ");

		$armor_slots = array_flip(self::ARMOR_INVENTORY_MAP);

		for($i = self::PLAYER_INVENTORY_SIZE; $i < $size; ++$i) {
			if(!isset($armor_slots[$i])) {
				$inventory->setItem($i, $barrier_item, false);
			}
		}

		$menu->setListener([$this, "syncInventory"]);
		$menu->setInventoryCloseListener([$this, "onInvClose"]);
		return $menu;
	}

	private function createEnderInvMenu(): InvMenu {
		return InvMenu::create(InvMenu::TYPE_CHEST)
			->setListener([$this, "syncEnderInventory"])
		->setInventoryCloseListener([$this, "onEnderInvClose"]);
	}
}