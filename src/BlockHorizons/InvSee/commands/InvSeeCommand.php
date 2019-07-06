<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class InvSeeCommand extends BaseCommand{

	protected function initCommand() : void{
		$this->setFlag(self::FLAG_DENY_CONSOLE);
	}

	public function onCommand(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!isset($args[0])){
			return false;
		}

		if(
			!$sender->hasPermission("invsee.inventory.view") &&
			(strtolower($args[0]) !== strtolower($sender->getName()) || !$sender->hasPermission("invsee.inventory.view.self"))
		){
			$sender->sendMessage(TextFormat::RED . "You don't have permission to view this inventory.");
			return true;
		}

		try{
			$player = $this->getLoader()->getInventoryHandler()->get($args[0]);
		}catch(InvalidArgumentException $e){
			$sender->sendMessage(TextFormat::RED . $e->getMessage());
			return true;
		}

		$player->getInventoryMenu()->send($sender);
		return true;
	}
}