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
 * @implements ArrayAccess<int|string, mixed>
 */
final class Configuration implements ArrayAccess{

	public static function fromConfig(Config $config) : self{
		return new self(basename($config->getPath()), $config->getAll());
	}

	/**
	 * @param string $file_name
	 * @param array<int|string, mixed> $configuration
	 * @param list<string> $parents
	 */
	public function __construct(
		readonly private string $file_name,
		readonly private array $configuration,
		readonly private array $parents = []
	){}

	public function getFileName() : string{
		return $this->file_name;
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function getConfiguration() : array{
		return $this->configuration;
	}

	public function offsetExists(mixed $offset) : bool{
		return array_key_exists($offset, $this->configuration);
	}

	public function offsetGet(mixed $offset) : mixed{
		array_key_exists($offset, $this->configuration) || $this->throwUndefinedConfiguration($offset);
		if(is_array($this->configuration[$offset])){
			return new self($this->file_name, $this->configuration[$offset], [...$this->parents, (string) $offset]);
		}
		return $this->configuration[$offset];
	}

	public function offsetSet(mixed $offset, mixed $value) : never{
		$this->throwInvalidOperation($offset ?? throw new RuntimeException("Offset cannot be null"), "Cannot write to configuration");
	}

	public function offsetUnset(mixed $offset) : never{
		$this->throwInvalidOperation($offset, "Cannot modify configuration");
	}

	public function throwInvalidOperation(int|string $offset, string $message = "") : never{
		throw new InvalidOperationConfigurationException($this->file_name, $offset, $message);
	}

	public function throwUndefinedConfiguration(int|string $offset, string $message = "") : never{
		throw new UndefinedConfigurationException($this->file_name, implode(".", [...$this->parents, $offset]), $message);
	}
}