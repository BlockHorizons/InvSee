<?php
namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\inventories\InvSeeEnderInventory;
use BlockHorizons\InvSee\inventories\InvSeeInventory;
use BlockHorizons\InvSee\inventories\InvSeePlayerInventory;
use BlockHorizons\InvSee\utils\SpyingPlayerData;

use muqsit\invmenu\InvMenuHandler;

use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\Player;

class InventoryHandler {

	const TYPE_ENDER_INVENTORY = InvSeeEnderInventory::class;
	const TYPE_PLAYER_INVENTORY = InvSeePlayerInventory::class;

	/** @var Loader */
	private $loader;

	/** @var SpyingPlayerData[] */
	private $spying = [];

	public function __construct(Loader $loader) {
		if(!InvMenuHandler::isRegistered()) {
			InvMenuHandler::register($loader);
		}

		$this->loader = $loader;
	}

	public function handleJoin(Player $player): void {
		if(isset($this->spying[$key = $player->getLowerCaseName()])) {
			$this->spying[$key]->onJoin($player);
		}
	}

	public function handleQuit(Player $player): void {
		if(isset($this->spying[$key = $player->getLowerCaseName()])) {
			$this->spying[$key]->onQuit($player);
		}
	}

	public function syncSpyerAction(SlotChangeAction $action): void {
		$inventory = $action->getInventory();
		if(isset($this->spying[$key = strtolower($inventory->getSpying())])) {
			$player = $this->spying[$key]->getPlayer();
			if($player !== null) {
				$inventory->syncSpyerAction($player, $action);
			}
		}
	}

	public function syncPlayerAction(Player $player, SlotChangeAction $action): void {
		$inventory = $action->getInventory();
		if(isset($this->spying[$key = $player->getLowerCaseName()])) {
			foreach($this->spying[$key]->getAll() as $menu) {
				$menu_inventory = $menu->getInventory();
				if($menu_inventory->canSpyInventory($inventory)) {
					$menu_inventory->syncPlayerAction($action);
				}
			}
		}
	}

	public function onInventoryClose(Player $player, InvSeeInventory $inventory): void {
		if(count($inventory->getViewers()) <= 1) {
			if($this->spying[$key = strtolower($inventory->getSpying())]->getPlayer() === null) {
				$inventory->syncOffline();
			}

			$this->spying[$key]->remove($inventory);
			if($this->spying[$key]->isEmpty()) {
				unset($this->spying[$key]);
			}
		}
	}

	public function send(Player $opener, string $player, string $inventory_class): bool {
		$data = $this->spying[$key = strtolower($player)] ?? ($this->spying[$key] = new SpyingPlayerData($player));

		$menu = $data->get($inventory_class);
		if($menu === null) {
			$menu = $data->create($inventory_class);
			$menu->setName($player . " инвентарь");
			$menu->setInventoryCloseListener([$this, "onInventoryClose"]);
			$menu->setListener([$this, "handleSpyInventoryTransaction"]);
		}

		return $menu->send($opener);
	}

	public function handleSpyInventoryTransaction(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action): bool {
		return $action->getInventory()->canModifySlot($player, $action->getSlot());
	}
}
