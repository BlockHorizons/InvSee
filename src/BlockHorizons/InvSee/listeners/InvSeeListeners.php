<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use pocketmine\inventory\InventoryListener;

final class InvSeeListeners{

	/**
	 * @param array<int, InventoryListener> $listeners
	 * @return array<int, InvSeeListener>
	 */
	public static function find(array $listeners) : array{
		$result = [];

		foreach($listeners as $listener){
			if($listener instanceof InvSeeListener){
				$result[spl_object_id($listener)] = $listener;
			}
		}

		return $result;
	}
}