<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class InvSeePlayerListEventListener implements Listener{

	public function __construct(
		readonly private InvSeePlayerList $player_list
	){}

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