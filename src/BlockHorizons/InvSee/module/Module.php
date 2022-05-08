<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

use BlockHorizons\InvSee\Loader;
use BlockHorizons\InvSee\utils\config\Configuration;

interface Module{

	public static function fromConfiguration(Configuration $configuration) : self;

	public function onEnable(Loader $loader) : void;

	public function onDisable(Loader $loader) : void;
}