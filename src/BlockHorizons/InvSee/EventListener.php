<?php
namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\inventories\InvSeeInventory;

use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\Player;

class EventListener implements Listener {

	/** @var InventoryHandler */
	private $handler;

	public function __construct(InventoryHandler $handler) {
		$this->handler = $handler;
	}

	/**
	 * @param PlayerJoinEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerJoin(PlayerJoinEvent $event): void {
		$this->handler->handleJoin($event->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event): void {
		$this->handler->handleQuit($event->getPlayer());
	}

	/**
	 * @param EntityInventoryChangeEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onEntityInventoryChange(EntityInventoryChangeEvent $event): void {
		$player = $event->getEntity();
		if($player instanceof Player) {
			$this->handler->syncPlayerAction($player, new SlotChangeAction($event instanceof EntityArmorChangeEvent ? $player->getArmorInventory() : $player->getInventory(), $event->getSlot(), $event->getOldItem(), $event->getNewItem()));
		}
	}

	/**
	 * @param InventoryTransactionEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event): void {
		$transaction = $event->getTransaction();
		foreach($transaction->getActions() as $action) {
			if($action instanceof SlotChangeAction) {
				$inventory = $action->getInventory();
				if($inventory instanceof InvSeeInventory) {
					$this->handler->syncSpyerAction($action);
				}else{
					$this->handler->syncPlayerAction($transaction->getSource(), $action);
				}
			}
		}
	}
}
