<?php

namespace Zinkil\pc\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class GamemodeCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("gm", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bChange the gamemode for a player");
		$this->setPermission("pc.command.gm");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.gm")){
			$player->sendMessage("§cYou cannot execute this command.");
			return;
		}
		if(!$player->isOp()){
			if($player->isTagged()){
				$player->sendMessage("§cYou cannot use this command while in combat.");
				return;
			}
		}
		if(!isset($args[0])){
			$player->sendMessage("§cProvide an argument: 0:1:2:3");
			return;
		}
		if(isset($args[0]) && !isset($args[1])){
			switch($args[0]){
				case "0":
				case "s":
				case "survival";
				$newgamemode="survival";
				if($player->getGamemode()==0){
					$player->sendMessage("§cYour gamemode is already set to ".$newgamemode.".");
					return;
				}
				$player->setGamemode(0);
				$player->setAllowFlight(false);
				$player->setFlying(false);
				$player->sendMessage("§aYou updated your gamemode to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechange");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "1":
				case "c":
				case "creative";
				$newgamemode="creative";
				if($player->getGamemode()==1){
					$player->sendMessage("§cYour gamemode is already set to ".$newgamemode.".");
					return;
				}
				$player->setGamemode(1);
				$player->setAllowFlight(true);
				$player->sendMessage("§aYou updated your gamemode to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechange");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "2":
				case "a":
				case "adventure";
				$newgamemode="adventure";
				if($player->getGamemode()==2){
					$player->sendMessage("§cYour gamemode is already set to ".$newgamemode.".");
					return;
				}
				$player->setGamemode(2);
				$player->setAllowFlight(false);
				$player->setFlying(false);
				$player->sendMessage("§aYou updated your gamemode to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechange");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "3":
				case "sp":
				case "spectator";
				$newgamemode="spectator";
				if($player->getGamemode()==3){
					$player->sendMessage("§cYour gamemode is already set to ".$newgamemode.".");
					return;
				}
				$player->setGamemode(3);
				$player->sendMessage("§aYou updated your gamemode to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechange");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				default:
				$player->sendMessage("§cProvide a valid argument: 0:1:2:3");
			}
		}else{
		//if(isset($args[0]) && isset($args[1])){
			if(!$player->hasPermission("pc.command.gmother")){
				$player->sendMessage("§cYou cannot update another players gamemode.");
				return;
			}
			if($this->plugin->getServer()->getPlayer($args[1])===null){
				$player->sendMessage("§cPlayer not found.");
				return;
			}
			switch($args[0]){
				case "0":
				case "s":
				case "survival";
				$newgamemode="survival";
				$target=$this->plugin->getServer()->getPlayer($args[1]);
				if($target->getGamemode()==0){
					$player->sendMessage("§c".$target->getName()."'s gamemode is already set to ".$newgamemode.".");
					return;
				}
				$target->setGamemode(0);
				$target->setAllowFlight(false);
				$target->setFlying(false);
				$player->sendMessage("§aYou updated ".$target->getName()."'s gamemode to ".$newgamemode.".");
				$target->sendMessage("§aYour gamemode was updated to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechangeother");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{target}", $target->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "1":
				case "c":
				case "creative";
				$newgamemode="creative";
				$target=$this->plugin->getServer()->getPlayer($args[1]);
				if($target->getGamemode()==1){
					$player->sendMessage("§c".$target->getName()."'s gamemode is already set to ".$newgamemode.".");
					return;
				}
				$target->setGamemode(1);
				$target->setAllowFlight(true);
				$player->sendMessage("§aYou updated ".$target->getName()."'s gamemode to ".$newgamemode.".");
				$target->sendMessage("§aYour gamemode was updated to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechangeother");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{target}", $target->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "2":
				case "a":
				case "adventure";
				$newgamemode="adventure";
				$target=$this->plugin->getServer()->getPlayer($args[1]);
				if($target->getGamemode()==2){
					$player->sendMessage("§c".$target->getName()."'s gamemode is already set to ".$newgamemode.".");
					return;
				}
				$target->setGamemode(2);
				$target->setAllowFlight(false);
				$target->setFlying(false);
				$player->sendMessage("§aYou updated ".$target->getName()."'s gamemode to ".$newgamemode.".");
				$target->sendMessage("§aYour gamemode was updated to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechangeother");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{target}", $target->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "3":
				case "sp":
				case "spectator";
				$newgamemode="spectator";
				$target=$this->plugin->getServer()->getPlayer($args[1]);
				if($target->getGamemode()==3){
					$player->sendMessage("§c".$target->getName()."'s gamemode is already set to ".$newgamemode.".");
					return;
				}
				$target->setGamemode(3);
				$player->sendMessage("§aYou updated ".$target->getName()."'s gamemode to ".$newgamemode.".");
				$target->sendMessage("§aYour gamemode was updated to ".$newgamemode.".");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("gamemodechangeother");
				$message=str_replace("{name}", $player->getName(), $message);
				$message=str_replace("{target}", $target->getName(), $message);
				$message=str_replace("{newgamemode}", $newgamemode, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				default:
				$player->sendMessage("§cProvide a valid argument: 0:1:2:3");
			}
		}
	}
}