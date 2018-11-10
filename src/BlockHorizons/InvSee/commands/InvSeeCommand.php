<?php
namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\InventoryHandler;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class InvSeeCommand extends BaseCommand {

	protected function initCommand(): void {
		$this->setFlag(self::FLAG_DENY_CONSOLE);
	}

	public function onCommand(CommandSender $sender, string $commandLabel, array $args): bool {
		if(!isset($args[0])) {
			return false;
		}

		if(!$this->getLoader()->getInventoryHandler()->send($sender, $args[0], InventoryHandler::TYPE_PLAYER_INVENTORY)) {
			$sender->sendMessage(TextFormat::RED . "You cannot view this inventory.");
			return true;
		}
		return true;
	}
}