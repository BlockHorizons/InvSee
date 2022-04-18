<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module\utils;

use InvalidArgumentException;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;

final class ModuleUtils{

	public static function assignPermissionDefault(Permission $permission, string $default) : void{
		$manager = PermissionManager::getInstance();
		$parent = match($default){
			"everyone" => $manager->getPermission(DefaultPermissions::ROOT_USER),
			"op" => $manager->getPermission(DefaultPermissions::ROOT_OPERATOR),
			"none" => null,
			default => throw new InvalidArgumentException("Invalid permission default \"{$default}\", expected one of: everyone, op, none")
		};
		$parent?->addChild($permission->getName(), true);
	}
}