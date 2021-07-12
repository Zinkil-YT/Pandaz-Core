<?php

namespace Zinkil\pc\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class MessagesCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("messages", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bEnable or disable staff messages");
		$this->setPermission("pc.command.messages");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player instanceof Player){
			return;
		}
		if(!$player->hasPermission("pc.command.messages")){
			$player->sendMessage("§cYou can't execute this command.");
			return;
		}
		if(!$player->isOp()){
			if($player->isTagged()){
				$player->sendMessage("§cYou cannot use this command while in combat.");
				return;
			}
		}
		if(!$player->isMessages()){
			$this->plugin->getStaffUtils()->messages($player, true);
		}else{
			$this->plugin->getStaffUtils()->messages($player, false);
		}
	}
}