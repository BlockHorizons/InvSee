<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\player\InvSeePlayerList;
use InvalidArgumentException;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class EnderInvSeeCommandExecutor implements CommandExecutor{

	public function __construct(
		private InvSeePlayerList $player_list
	){}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "This command can only be used as a player.");
			return true;
		}

		if(!isset($args[0])){
			return false;
		}

		if(
			!$sender->hasPermission("invsee.enderinventory.view") &&
			(strtolower($args[0]) !== strtolower($sender->getName()) || !$sender->hasPermission("invsee.enderinventory.view.self"))
		){
			$sender->sendMessage(TextFormat::RED . "You don't have permission to view this inventory.");
			return true;
		}

		try{
			$player = $this->player_list->getOrCreate($args[0]);
		}catch(InvalidArgumentException $e){
			$sender->sendMessage(TextFormat::RED . $e->getMessage());
			return true;
		}

		$player->getEnderChestInventoryMenu()->send($sender);
		return true;
	}
}