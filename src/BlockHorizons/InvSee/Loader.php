<?php
namespace BlockHorizons\InvSee;

use BlockHorizons\InvSee\commands\BaseCommand;

use pocketmine\plugin\PluginBase;

class Loader extends PluginBase {

	/** @var InventoryHandler */
	private $invhandler;

	public function onEnable(): void {
		$this->invhandler = new InventoryHandler($this);
		BaseCommand::registerDefaults($this);

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this->getInventoryHandler()), $this);
	}

	public function getInventoryHandler(): InventoryHandler {
		return $this->invhandler;
	}
}