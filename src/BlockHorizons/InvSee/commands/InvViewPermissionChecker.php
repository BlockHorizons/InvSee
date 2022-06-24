<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use Closure;
use Generator;
use pocketmine\player\Player;

final class InvViewPermissionChecker{

	/** @var array<int, array{Closure(Player, string) : ?bool, int}> */
	private array $view_permission_checker = [];

	public function __construct(){
	}

	/**
	 * @param Closure(Player, string) : ?bool $checker
	 * @param int $priority
	 */
	public function register(Closure $checker, int $priority = 0) : void{
		$this->view_permission_checker[spl_object_id($checker)] = [$checker, $priority];
		uasort($this->view_permission_checker, static fn(array $a, array $b) : int => $a[1] <=> $b[1]);
	}

	/**
	 * @param Closure(Player, string) : ?bool $checker
	 */
	public function unregister(Closure $checker) : void{
		unset($this->view_permission_checker[spl_object_id($checker)]);
	}

	/**
	 * @return Generator<Closure(Player, string) : ?bool>
	 */
	public function getAll() : Generator{
		foreach($this->view_permission_checker as [$checker]){
			yield $checker;
		}
	}
}