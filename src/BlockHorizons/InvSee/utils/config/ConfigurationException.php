<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\config;

use RuntimeException;
use Throwable;

abstract class ConfigurationException extends RuntimeException{

	public function __construct(
		readonly public string $file_name,
		readonly public int|string $offset,
		string $message,
		int $code = 0,
		?Throwable $previous = null
	){
		parent::__construct($message, $code, $previous);
	}
}