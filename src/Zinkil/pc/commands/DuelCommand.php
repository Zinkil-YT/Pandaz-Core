<?php

declare(strict_types=1);

namespace Zinkil\pc\commands;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\duels\DuelInvite;

class DuelCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("duel", $plugin);
		$this->plugin=$plugin;
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if($player->isTagged()){
			$player->sendMessage("§cYou cannot use this command while in combat.");
			return;
		}
		$duel=$this->plugin->getDuelHandler()->getDuelFromSpec($player);
		if($this->plugin->getDuelHandler()->isInDuel($player) or $this->plugin->getDuelHandler()->isInBotDuel($player) or !is_null($duel)){
			$player->sendMessage("§cYou cannot use this command while in a duel.");
			return;
		}
		if(!isset($args[0])){
			$player->sendMessage("§cYou must provide a player.");
			return;
		}
		if($this->plugin->getServer()->getPlayer($args[0])===null){
			$player->sendMessage("§cPlayer not found.");
			return;
		}
		$target=$this->plugin->getServer()->getPlayer($args[0]);
		if($target->getName()==$player->getName()){
			$player->sendMessage("§cYou cannot duel yourself.");
			return;
		}
		$target=$this->plugin->getServer()->getPlayer($args[0]);
		if($target->getLevel()->getName()!=Core::LOBBY){
			$player->sendMessage("§cThis player cannot duel at the moment.");
			return;
		}
		if(!isset($args[1])){
			$player->sendMessage("§cYou must provide a mode.");
			return;
		}
		$target=$this->plugin->getServer()->getPlayer($args[0]);
		$mode=$args[1];
		switch($mode){
			case "nodebuff":
			$mode="NoDebuff";
			$player->sendMessage("§aYou sent a ".$mode." duel request to ".Utils::getPlayerDisplayName($target).".");
			$target->sendMessage("§a".Utils::getPlayerDisplayName($player)." sent you a ".$mode." duel request.");
			$invite=new DuelInvite($mode, $player->getName(), $target->getName());
			$this->plugin->duelInvites[]=$invite;
			break;
			default:
			$player->sendMessage("§cYou must provide a valid mode.");
			break;
		}
	}
}