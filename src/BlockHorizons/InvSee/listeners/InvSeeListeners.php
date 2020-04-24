<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use pocketmine\inventory\InventoryChangeListener;

final class InvSeeListeners{

	/**
	 * @param InventoryChangeListener[] $listeners
	 * @return InvSeeListener[]
	 */
	public static function find(array $listeners) : array{
		$result = [];

		foreach($listeners as $listener){
			if($listener instanceof InvSeeListener){
				$result[] = $listener;
			}
		}

		return $result;
	}
}