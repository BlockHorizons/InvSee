<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use pocketmine\inventory\Inventory;

class PlayerEnderInventoryChangeListener implements InvSeeListener{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory) : void{
		$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
		$this->inventory->removeChangeListeners(...$listeners);
		$this->inventory->setContents($inventory->getContents());
		$this->inventory->addChangeListeners(...$listeners);
	}

	public function onSlotChange(Inventory $inventory, int $slot) : void{
		$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
		$this->inventory->removeChangeListeners(...$listeners);
		$this->inventory->setItem($slot, $inventory->getItem($slot));
		$this->inventory->addChangeListeners(...$listeners);
	}
}