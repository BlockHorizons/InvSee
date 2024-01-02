<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

final class ModuleInfo{

	/**
	 * @param string $identifier
	 * @param string $name
	 * @param string $description
	 * @param class-string<Module> $module_class
	 */
	public function __construct(
		readonly public string $identifier,
		readonly public string $name,
		readonly public string $description,
		readonly public string $module_class
	){}
}