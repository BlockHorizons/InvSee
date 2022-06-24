<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module\utils;

use Closure;
use InvalidArgumentException;
use pocketmine\event\HandlerListManager;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use RuntimeException;

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

	/**
	 * @template TEvent of \pocketmine\event\Event
	 * @param class-string<TEvent> $event_class
	 * @param int $priority
	 * @param Closure(TEvent) : void $event_handler
	 * @return Closure() : void
	 */
	public static function getEventListenerUnregisterExecutor(string $event_class, int $priority, Closure $event_handler) : Closure{
		foreach(HandlerListManager::global()->getListFor($event_class)->getListenersByPriority($priority) as $entry){
			if($entry->getHandler() === $event_handler){
				return static fn() => HandlerListManager::global()->getListFor($event_class)->unregister($entry);
			}
		}
		throw new RuntimeException("Could not find registered listener");
	}
}