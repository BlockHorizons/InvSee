<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use BlockHorizons\InvSee\utils\InvCombiner;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

final class PlayerOffhandInventoryListener implements InvSeeListener{

	public function __construct(
		readonly private Inventory $inventory
	){}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$listeners = InvSeeListeners::find($this->inventory->getListeners()->toArray());
		$this->inventory->getListeners()->remove(...$listeners);
		$this->inventory->setItem(InvCombiner::OFFHAND_SLOT_OFFSET, $inventory->getItem(0));
		$this->inventory->getListeners()->add(...$listeners);
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		$listeners = InvSeeListeners::find($this->inventory->getListeners()->toArray());
		$this->inventory->getListeners()->remove(...$listeners);
		$this->inventory->setItem(InvCombiner::OFFHAND_SLOT_OFFSET, $inventory->getItem(0));
		$this->inventory->getListeners()->add(...$listeners);
	}
}