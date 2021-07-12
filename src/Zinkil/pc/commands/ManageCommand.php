<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class ManageCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("manage", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bChange a player stats");
		$this->setPermission("pc.command.manage");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.manage")){
			$player->sendMessage("§cYou cannot execute this command.");
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
		if(!isset($args[1])){
			$player->sendMessage("§cYou must provide an argument. kills:deaths");
			return;
		}
		$target=$this->plugin->getServer()->getPlayer($args[0]);
		if($target instanceof Player){
			switch($args[1]){
				case "kills":
				if(!isset($args[2])){
					$player->sendMessage("§cYou must provide an argument. add:subtract:set");
					return;
				}
				switch($args[2]){
					case "add":
					if(!isset($args[3])){
						$player->sendMessage("§cYou must provide a numerical value.");
						return;
					}
					$val=intval($args[3]);
					$old=$this->plugin->getDatabaseHandler()->getKills($target->getName());
					$this->plugin->getDatabaseHandler()->setKills($target, $old + $val);
					$player->sendMessage("§aSuccessfully added ".$args[3]." kills to ".$target->getName()."'s total. Previous-Total: ".$old." Updated-Total: ".$this->plugin->getDatabaseHandler()->getKills($target->getName()).".");
					break;
					case "subtract":
					if(!isset($args[3])){
						$player->sendMessage("§cYou must provide a numerical value.");
						return;
					}
					$val=intval($args[3]);
					$old=$this->plugin->getDatabaseHandler()->getKills($target->getName());
					$this->plugin->getDatabaseHandler()->setKills($target, $old - $val);
					$player->sendMessage("§aSuccessfully subtracted ".$args[3]." kills from ".$target->getName()."'s total. Previous-Total: ".$old." Updated-Total: ".$this->plugin->getDatabaseHandler()->getKills($target->getName()).".");
					break;
					case "set":
					if(!isset($args[3])){
						$player->sendMessage("§cYou must provide a numerical value.");
						return;
					}
					$val=intval($args[3]);
					$old=$this->plugin->getDatabaseHandler()->getKills($target->getName());
					$this->plugin->getDatabaseHandler()->setKills($target, $val);
					$player->sendMessage("§aSuccessfully set ".$target->getName()."'s kills total to ".$val.". Previous-Total: ".$old." Updated-Total: ".$this->plugin->getDatabaseHandler()->getKills($target->getName()).".");
					break;
					default:
					$player->sendMessage("§cYou must provide a valid argument. add:subtract:set");
				}
				break;
				case "deaths":
				if(!isset($args[2])){
					$player->sendMessage("§cYou must provide an argument. add:subtract:set");
					return;
				}
				switch($args[2]){
					case "add":
					if(!isset($args[3])){
						$player->sendMessage("§cYou must provide a numerical value.");
						return;
					}
					$val=intval($args[3]);
					$old=$this->plugin->getDatabaseHandler()->getDeaths($target->getName());
					$this->plugin->getDatabaseHandler()->setDeaths($target, $old + $val);
					$player->sendMessage("§aSuccessfully added ".$args[3]." deaths to ".$target->getName()."'s total. Previous-Total: ".$old." Updated-Total: ".$this->plugin->getDatabaseHandler()->getDeaths($target->getName()).".");
					break;
					case "subtract":
					if(!isset($args[3])){
						$player->sendMessage("§cYou must provide a numerical value.");
						return;
					}
					$val=intval($args[3]);
					$old=$this->plugin->getDatabaseHandler()->getDeaths($target->getName());
					$this->plugin->getDatabaseHandler()->setDeaths($target, $old - $val);
					$player->sendMessage("§aSuccessfully subtracted ".$args[3]." deaths from ".$target->getName()."'s total. Previous-Total: ".$old." Updated-Total: ".$this->plugin->getDatabaseHandler()->getDeaths($target->getName()).".");
					break;
					case "set":
					if(!isset($args[3])){
						$player->sendMessage("§cYou must provide a numerical value.");
						return;
					}
					$val=intval($args[3]);
					$old=$this->plugin->getDatabaseHandler()->getDeaths($target->getName());
					$this->plugin->getDatabaseHandler()->setDeaths($target, $val);
					$player->sendMessage("§aSuccessfully set ".$target->getName()."'s deaths total to ".$val.". Previous-Total: ".$old." Updated-Total: ".$this->plugin->getDatabaseHandler()->getDeaths($target->getName()).".");
					break;
					default:
					$player->sendMessage("§cYou must provide a valid argument. add:subtract:set");
				}
				break;
				default:
				$player->sendMessage("§cYou must provide a valid argument. coins:kills:deaths");
			}
		}
	}
}