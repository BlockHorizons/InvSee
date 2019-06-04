<?php
namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\inventories\InvSeeInventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;

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
	 * @param InventoryTransactionEvent $event
	 * @priority MONITOR
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
