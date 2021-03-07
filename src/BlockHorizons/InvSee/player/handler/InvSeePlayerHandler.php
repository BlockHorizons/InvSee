<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\player\handler;

use BlockHorizons\InvSee\player\InvSeePlayer;

interface InvSeePlayerHandler{

	public function init(InvSeePlayer $player) : void;

	public function destroy(InvSeePlayer $player) : void;
}