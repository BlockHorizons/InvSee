<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

final class InvSeeListeners{

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