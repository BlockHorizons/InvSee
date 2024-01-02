<?php

declare(strict_types=1);

namespace BlockHorizons\InvSee\commands;

use BlockHorizons\InvSee\player\InvSeePlayerList;
use BlockHorizons\InvSee\utils\playerselector\PlayerSelector;
use InvalidArgumentException;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function implode;

final class InvSeeCommandExecutor implements CommandExecutor{

	readonly public InvViewPermissionChecker $view_permission_checker;

	public function __construct(
		readonly private InvSeePlayerList $player_list,
		readonly private PlayerSelector $player_selector
	){
		$this->view_permission_checker = new InvViewPermissionChecker();
		$this->view_permission_checker->register(static function(Player $player, string $viewing) : ?bool{
			if(
				!$player->hasPermission("invsee.inventory.view") &&
				(strtolower($viewing) !== strtolower($player->getName()) || !$player->hasPermission("invsee.inventory.view.self"))
			){
				$player->sendMessage(TextFormat::RED . "You don't have permission to view this inventory.");
				return false;
			}
			return true;
		}, 0);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!($sender instanceof Player)){
			$sender->sendMessage(TextFormat::RED . "This command can only be used as a player.");
			return true;
		}

		if(!isset($args[0])){
			return false;
		}

		$who = $this->player_selector->select(implode(" ", $args));
		foreach($this->view_permission_checker->getAll() as $checker){
			$result = $checker($sender, $who);
			if($result === null){ // result = null
				continue;
			}
			if($result){ // result = true
				break;
			}
			return true; // result = false
		}

		try{
			$player = $this->player_list->getOrCreate($who);
		}catch(InvalidArgumentException $e){
			$sender->sendMessage(TextFormat::RED . $e->getMessage());
			return true;
		}

		$player->inventory_menu->send($sender);
		return true;
	}
}