<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player\handler;

use BlockHorizons\InvSee\player\InvSeePlayer;
use RuntimeException;

final class DestroyedInvSeePlayerHandler implements InvSeePlayerHandler{

	public static function instance() : self{
		static $instance = null;
		return $instance ??= new self();
	}

	private function __construct(){
	}

	public function init(InvSeePlayer $player) : void{
	}

	public function destroy(InvSeePlayer $player) : void{
		throw new RuntimeException("Cannot destroy " . self::class . " player handler");
	}
}