<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener{

	/** @var InventoryHandler */
	protected $handler;

	public function __construct(Loader $loader){
		$this->handler = $loader->getInventoryHandler();
	}

	/**
	 * @param PlayerLoginEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$this->handler->onJoin($event->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$this->handler->onQuit($event->getPlayer());
	}
}