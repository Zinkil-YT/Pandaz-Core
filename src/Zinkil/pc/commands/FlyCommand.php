<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;

class FlyCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("fly", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bEnable or disable fly for a player");
		$this->setPermission("pc.command.fly");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.fly")){
			if($this->plugin->getDatabaseHandler()->voteAccessExists($player)){
			}else{
				$player->sendMessage("§cYou cannot execute this command.");
				return;
			}
		}
		if($this->plugin->getDuelHandler()->isInDuel($player) or $this->plugin->getDuelHandler()->isInPartyDuel($player) or $this->plugin->getDuelHandler()->isInBotDuel($player)){
			$player->sendMessage("§cYou cannot use this command while in a duel.");
			return;
		}
		$level=$player->getLevel()->getName();
		if($level!=="lobby"){
			$player->sendMessage("§cYou cannot enable fly here.");
			return;
		}
		if($player->getAllowFlight()===false){
			$player->setFlying(true);
			$player->setAllowFlight(true);
			$player->sendMessage("§aYou enabled flight.");
		}else{
			$player->setFlying(false);
			$player->setAllowFlight(false);
			$player->sendMessage("§aYou disabled flight.");
		}
	}
}