<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class PlayerEnderInventoryListener implements InvSeeListener{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$listeners = InvSeeListeners::find($this->inventory->getListeners());
		$this->inventory->removeListeners(...$listeners);
		$this->inventory->setContents($inventory->getContents());
		$this->inventory->addListeners(...$listeners);
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		$listeners = InvSeeListeners::find($this->inventory->getListeners());
		$this->inventory->removeListeners(...$listeners);
		$this->inventory->setItem($slot, $inventory->getItem($slot));
		$this->inventory->addListeners(...$listeners);
	}
}