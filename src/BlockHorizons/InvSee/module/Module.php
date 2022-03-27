<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

use BlockHorizons\InvSee\Loader;

interface Module{

	/**
	 * @param mixed[] $configuration
	 * @return self
	 */
	public static function fromConfiguration(array $configuration) : self;

	public function onEnable(Loader $loader) : void;

	public function onDisable(Loader $loader) : void;
}