<?php
namespace BlockHorizons\InvSee\utils;

use BlockHorizons\InvSee\inventories\InvSeeInventory;

use muqsit\invmenu\InvMenu;

use pocketmine\Player;
use pocketmine\Server;

class SpyingPlayerData {

	/** @var InvMenu[] */
	protected $menus = [];

	/** @var string */
	protected $spying;

	/** @var string|null */
	protected $rawUUID;

	public function __construct(string $spying) {
		$this->spying = $spying;

		$player = Server::getInstance()->getPlayerExact($spying);
		if($player !== null) {
			$this->rawUUID = $player->getRawUniqueId();
		}
	}

	public function getSpying(): string {
		return $this->spying;
	}

	public function add(InvMenu $menu): void {
		if(isset($this->menus[$class = get_class($inventory = $menu->getInventory())])) {
			throw new \RuntimeError("Tried adding an already existing inventory.");
		}

		$this->menus[$class] = $menu;
		$inventory->initialize($this);
	}

	public function get(string $inventory_class): ?InvMenu {
		return $this->menus[$inventory_class] ?? null;
	}

	public function remove(InvSeeInventory $inventory): void {
		unset($this->menus[get_class($inventory)]);
		$inventory->deInitialize($this);
	}

	public function isEmpty(): bool {
		return empty($this->menus);
	}

	public function create(string $inventory_class): InvMenu {
		$this->add($menu = InvMenu::create($inventory_class, $this));
		return $menu;
	}

	public function getAll(): array {
		return $this->menus;
	}

	public function onJoin(Player $player): void {
		$this->rawUUID = $player->getRawUniqueId();
		foreach($this->getAll() as $menu) {
			$menu->getInventory()->syncOnline($player);
		}
	}

	public function onQuit(Player $player): void {
		$this->rawUUID = null;
	}

	public function getPlayer(): ?Player {
		return $this->rawUUID !== null ? Server::getInstance()->getPlayerByRawUUID($this->rawUUID) : null;
	}
}