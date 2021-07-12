<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class PingCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("ping", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bGet a player ping");
		$this->setAliases(["ms"]);
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!isset($args[0]) and $player instanceof Player){
			$player->sendMessage("§aYour ping is ".$player->getPing()."ms.");
			return;
		}
		if(isset($args[0]) and $target=$this->plugin->getServer()->getPlayer($args[0])===null){
			$player->sendMessage("§cPlayer not found.");
			return;
		}
		$target=$this->plugin->getServer()->getPlayer($args[0]);
		if($target instanceof Player){
			$player->sendMessage("§a".$target->getName()."'s ping is ".$target->getPing()."ms.");
		}
	}
}