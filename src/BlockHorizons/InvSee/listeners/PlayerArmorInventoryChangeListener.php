<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use BlockHorizons\InvSee\utils\InvCombiner;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class PlayerArmorInventoryChangeListener implements InvSeeListener{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
		$this->inventory->removeChangeListeners(...$listeners);
		foreach(InvCombiner::ARMOR_TO_MENU_SLOTS as $armor_slot => $menu_slot){
			$this->inventory->setItem($menu_slot, $inventory->getItem($armor_slot));
		}
		$this->inventory->addChangeListeners(...$listeners);

		foreach($this->inventory->getViewers() as $viewer){
			$viewer->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		if(isset(InvCombiner::ARMOR_TO_MENU_SLOTS[$slot])){
			$listeners = InvSeeListeners::find($this->inventory->getChangeListeners());
			$this->inventory->removeChangeListeners(...$listeners);
			$this->inventory->setItem(InvCombiner::ARMOR_TO_MENU_SLOTS[$slot], $inventory->getItem($slot));
			$this->inventory->addChangeListeners(...$listeners);
		}
	}
}