<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player;

use BlockHorizons\InvSee\Loader;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class InvSeePlayerListEventListener implements Listener{

	protected InvSeePlayerList $player_list;

	public function __construct(Loader $loader){
		$this->player_list = $loader->getPlayerList();
	}

	/**
	 * @param PlayerJoinEvent $event
	 * @priority LOWEST
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$this->player_list->onPlayerJoin($event->getPlayer());
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$this->player_list->onPlayerQuit($event->getPlayer());
	}
}