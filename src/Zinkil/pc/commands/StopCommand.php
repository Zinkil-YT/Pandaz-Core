<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\tasks\onetime\RestartTask;

class StopCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("stop", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bRestart server command");
		$this->setPermission("pc.command.stop");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.stop")){
			$player->sendMessage("§cYou cannot execute this command.");
			return;
		}
		$this->plugin->getServer()->broadcastMessage("§bPandaz will now preform a restart.");
		$this->plugin->getScheduler()->scheduleDelayedRepeatingTask(new RestartTask($this->plugin), 60, 1);
	}
}