<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\listeners;

use BlockHorizons\InvSee\utils\InvCombiner;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;

class InvSeeArmorInventoryListener implements InvSeeListener{

	/** @var Inventory */
	protected $inventory;

	public function __construct(Inventory $inventory){
		$this->inventory = $inventory;
	}

	public function onContentChange(Inventory $inventory, array $old_contents) : void{
		$listeners = InvSeeListeners::find($this->inventory->getListeners()->toArray());
		$this->inventory->getListeners()->remove(...$listeners);
		foreach(InvCombiner::MENU_TO_ARMOR_SLOTS as $menu_slot => $armor_slot){
			$this->inventory->setItem($armor_slot, $inventory->getItem($menu_slot));
		}
		$this->inventory->getListeners()->add(...$listeners);

		foreach($this->inventory->getViewers() as $viewer){
			$viewer->getNetworkSession()->getInvManager()->syncContents($this->inventory);
		}
	}

	public function onSlotChange(Inventory $inventory, int $slot, Item $old_item) : void{
		if(isset(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot])){
			$listeners = InvSeeListeners::find($this->inventory->getListeners()->toArray());
			$this->inventory->getListeners()->remove(...$listeners);
			$this->inventory->setItem(InvCombiner::MENU_TO_ARMOR_SLOTS[$slot], $inventory->getItem($slot));
			$this->inventory->getListeners()->add(...$listeners);
		}
	}
}