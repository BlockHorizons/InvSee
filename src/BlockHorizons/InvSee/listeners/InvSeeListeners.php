<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use Ds\Set;
use pocketmine\inventory\InventoryListener;

final class InvSeeListeners{

	/**
	 * @param Set<InventoryListener> $listeners
	 * @return Set<InvSeeListener>
	 */
	public static function find(Set $listeners) : Set{
		$result = new Set();

		foreach($listeners as $listener){
			if($listener instanceof InvSeeListener){
				$result->add($listener);
			}
		}

		return $result;
	}
}