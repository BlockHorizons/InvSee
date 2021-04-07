<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class PlayerInventoryListener implements InvSeeListener{

	protected Inventory $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$listeners = InvSeeListeners::find($this->inventory->getListeners()->toArray());
		$this->inventory->getListeners()->remove(...$listeners);
		foreach($inventory->getContents() as $slot => $item){
			if($slot < 36){
				$this->inventory->setItem($slot, $item);
			}
		}
		$this->inventory->getListeners()->add(...$listeners);

		foreach($this->inventory->getViewers() as $viewer){
			$viewer->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		if($slot < 36){
			$listeners = InvSeeListeners::find($this->inventory->getListeners()->toArray());
			$this->inventory->getListeners()->remove(...$listeners);
			$this->inventory->setItem($slot, $inventory->getItem($slot));
			$this->inventory->getListeners()->add(...$listeners);
		}
	}
}