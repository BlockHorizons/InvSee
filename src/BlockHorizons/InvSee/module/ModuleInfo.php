<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\module;

final class ModuleInfo{

	/**
	 * @param string $identifier
	 * @param string $name
	 * @param string $description
	 * @param string|Module $module_class
	 *
	 * @phpstan-param class-string<Module> $module_class
	 */
	public function __construct(
		public string $identifier,
		public string $name,
		public string $description,
		public string $module_class
	){}
}