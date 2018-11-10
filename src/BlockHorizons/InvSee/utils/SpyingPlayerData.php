<?php
namespace BlockHorizons\InvSee\utils;

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
		if(isset($this->menus[$class = get_class($menu->getInventory())])) {
			throw new \RuntimeError("Tried adding an already existing inventory.");
		}

		$this->menus[$class] = $menu;
	}

	public function get(string $inventory_class): ?InvMenu {
		return $this->menus[$inventory_class] ?? null;
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