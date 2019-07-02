<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use BlockHorizons\InvSee\utils\InvCombiner;
use pocketmine\inventory\Inventory;

class InvSeeArmorInventoryChangeListener implements InvSeeListener{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory) : void{
		$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
		$this->inventory->removeChangeListeners(...$listeners);
		foreach(InvCombiner::MENU_TO_ARMOR_SLOTS as $menu_slot => $armor_slot){
			$this->inventory->setItem($armor_slot, $inventory->getItem($menu_slot));
		}
		$this->inventory->addChangeListeners(...$listeners);

		foreach($this->inventory->getViewers() as $viewer){
			$viewer->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

	public function onSlotChange(Inventory $inventory, int $slot) : void{
		if(isset(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot])){
			$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
			$this->inventory->removeChangeListeners(...$listeners);
			$this->inventory->setItem(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot], $inventory->getItem($slot));
			$this->inventory->addChangeListeners(...$listeners);
		}
	}
}