<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\config;

use ArrayAccess;
use pocketmine\utils\Config;
use RuntimeException;
use function array_key_exists;
use function basename;
use function implode;
use function is_array;

/**
 * @phpstan-implements ArrayAccess<int|string, mixed>
 */
final class Configuration implements ArrayAccess{

	public static function fromConfig(Config $config) : self{
		return new self(basename($config->getPath()), $config->getAll());
	}

	/**
	 * @param string $file_name
	 * @param mixed[] $configuration
	 * @param string[] $parents
	 *
	 * @phpstan-param array<int|string, mixed> $configuration
	 * @phpstan-param array<string> $parents
	 */
	public function __construct(
		private string $file_name,
		private array $configuration,
		private array $parents = []
	){}

	public function offsetExists(mixed $offset) : bool{
		return array_key_exists($offset, $this->configuration);
	}

	public function offsetGet(mixed $offset) : mixed{
		if(!array_key_exists($offset, $this->configuration)){
			$this->throwUndefinedConfiguration($offset);
		}

		if(is_array($this->configuration[$offset])){
			return new self($this->file_name, $this->configuration[$offset], [...$this->parents, (string) $offset]);
		}

		return $this->configuration[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value) : void{
		$this->throwInvalidOperation($offset ?? throw new RuntimeException("Offset cannot be null"), "Cannot write to configuration");
	}

	public function offsetUnset(mixed $offset) : void{
		$this->throwInvalidOperation($offset, "Cannot modify configuration");
	}

	public function throwInvalidOperation(int|string $offset, string $message = "") : void{
		throw new InvalidOperationConfigurationException($this->file_name, $offset, $message);
	}

	public function throwUndefinedConfiguration(int|string $offset, string $message = "") : void{
		throw new UndefinedConfigurationException($this->file_name, implode(".", [...$this->parents, $offset]), $message);
	}
}