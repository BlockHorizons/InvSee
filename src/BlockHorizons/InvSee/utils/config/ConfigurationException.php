<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\config;

use RuntimeException;
use Throwable;

abstract class ConfigurationException extends RuntimeException{

	public function __construct(
		private string $file_name,
		private int|string $offset,
		string $message,
		int $code = 0,
		?Throwable $previous = null
	){
		parent::__construct($message, $code, $previous);
	}

	public function getFileName() : string{
		return $this->file_name;
	}

	public function getOffset() : int|string{
		return $this->offset;
	}
}