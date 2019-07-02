<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\commands\BaseCommand;
use pocketmine\plugin\PluginBase;

class Loader extends PluginBase{

	/** @var InventoryHandler */
	private $handler;

	public function onEnable() : void{
		$this->handler = new InventoryHandler($this);
		BaseCommand::registerDefaults($this);

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	public function getInventoryHandler() : InventoryHandler{
		return $this->handler;
	}
}