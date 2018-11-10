<?php
namespace BlockHorizons\InvSee\inventories;

use BlockHorizons\InvSee\utils\SpyingPlayerData;

use muqsit\invmenu\InvMenu;

trait InvSeeInventoryTrait {

	/** @var string */
	protected $spying;

	public function __construct(InvMenu $menu, SpyingPlayerData $spying_player_data, int $size = null, string $title = null) {
		$this->spying = $spying_player_data->getSpying();
		parent::__construct($menu, $this->getSpyerContents(), $size, $title);
	}

	public function getSpying(): string {
		return $this->spying;
	}

	public function initialize(SpyingPlayerData $data): void {
	}

	public function deInitialize(SpyingPlayerData $data): void {
	}
}