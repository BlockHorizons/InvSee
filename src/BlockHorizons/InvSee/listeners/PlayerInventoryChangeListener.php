<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use pocketmine\inventory\Inventory;

class PlayerInventoryChangeListener implements InvSeeListener{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory) : void{
		$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
		$this->inventory->removeChangeListeners(...$listeners);
		foreach($inventory->getContents() as $slot => $item){
			if($slot < 36){
				$this->inventory->setItem($slot, $item, false);
			}
		}
		$this->inventory->addChangeListeners(...$listeners);

		foreach($this->inventory->getViewers() as $viewer){
			$viewer->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

	public function onSlotChange(Inventory $inventory, int $slot) : void{
		if($slot < 36){
			$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
			$this->inventory->removeChangeListeners(...$listeners);
			$this->inventory->setItem($slot, $inventory->getItem($slot));
			$this->inventory->addChangeListeners(...$listeners);
		}
	}
}