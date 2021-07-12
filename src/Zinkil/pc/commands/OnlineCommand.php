<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;

class OnlineCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("online", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bGet total players on the server");
		$this->setPermission("pc.command.online");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.online")){
			$player->sendMessage("§cYou cannot execute this command.");
			return;
		}
		foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
			if($online->getDisplayName()!=$online->getName()){
				$onlinePlayers[]=$online->getName()." §7(".$online->getDisplayName().")§r";
			}else{
				$onlinePlayers[]=$online->getName();
			}
		}
		$count=count($this->plugin->getServer()->getOnlinePlayers());
		$max=$this->plugin->getServer()->getMaxPlayers();
		if($count==0){
			$player->sendMessage("§cThere are no players online.");
		}else{
			$player->sendMessage("§b".$count."/".$max." §3-§r ".implode(", ", $onlinePlayers));
		}
	}
}