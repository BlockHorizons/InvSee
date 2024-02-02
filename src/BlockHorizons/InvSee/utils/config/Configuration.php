<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\utils\config;

use pocketmine\utils\Config;
use function array_push;
use function basename;
use function implode;

final class Configuration{

	public static function fromConfig(Config $config) : self{
		return new self(basename($config->getPath()), $config->getAll(), []);
	}

	/**
	 * @param string $file_name
	 * @param array<int|string, mixed> $configuration
	 * @param list<int|string> $offset
	 */
	public function __construct(
		readonly public string $file_name,
		readonly public array $configuration,
		readonly public array $offset
	){}

	/**
	 * @return array<int|string, mixed>
	 */
	public function getConfiguration() : array{
		return $this->configuration;
	}

	public function get(int|string ...$keys) : mixed{
		$value = $this->configuration;
		$traversed = [];
		foreach($keys as $index => $key){
			$traversed[] = $index;
			isset($value[$key]) || $this->throwUndefinedConfiguration($traversed);
			$value = $value[$key];
		}
		return $value;
	}

	public function getConfig(int|string ...$keys) : self{
		$offsets = $this->offset;
		array_push($offsets, ...$keys);
		return new self($this->file_name, $this->get(...$keys), $offsets);
	}

	/**
	 * @param array<int|string> $offset
	 * @param string $message
	 * @return never
	 */
	public function throwUndefinedConfiguration(array $offset, string $message = "") : never{
		$offsets = $this->offset;
		array_push($offsets, ...$offset);
		throw new UndefinedConfigurationException($this->file_name, implode(".", $offsets), $message);
	}
}