<?php
namespace BlockHorizons\InvSee;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {

	/** @var InventoryHandler */
	private $handler;

	public function __construct(InventoryHandler $handler) {
		$this->handler = $handler;
	}

	/**
	 * @param PlayerJoinEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerJoin(PlayerJoinEvent $event): void {
		$this->handler->enableSyncing($event->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event): void {
		$this->handler->disableSyncing($event->getPlayer());
	}
}