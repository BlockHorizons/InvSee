<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\Loader;
use InvalidArgumentException;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class EnderInvSeeCommand extends BaseCommand{

	public function __construct(Loader $loader, string $label, array $aliases = []){
		parent::__construct($loader, $label, "View a player's ender inventory.", "/{$label} <player>", $aliases);
	}

	protected function initCommand() : void{
		$this->setFlag(self::FLAG_DENY_CONSOLE);
	}

	public function onCommand(CommandSender $sender, string $commandLabel, array $args) : bool{
		if(!isset($args[0])){
			return false;
		}

		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "This command can only be used as a player.");
			return true;
		}

		if(
			!$sender->hasPermission("invsee.enderinventory.view") &&
			(strtolower($args[0]) !== strtolower($sender->getName()) || !$sender->hasPermission("invsee.enderinventory.view.self"))
		){
			$sender->sendMessage(TextFormat::RED . "You don't have permission to view this inventory.");
			return true;
		}

		try{
			$player = $this->getLoader()->getPlayerList()->getOrCreate($args[0]);
		}catch(InvalidArgumentException $e){
			$sender->sendMessage(TextFormat::RED . $e->getMessage());
			return true;
		}

		$player->getEnderChestInventoryMenu()->send($sender);
		return true;
	}
}