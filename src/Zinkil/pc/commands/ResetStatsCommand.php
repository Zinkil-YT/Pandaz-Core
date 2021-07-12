<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class ResetStatsCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("reset", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bReset a player stats");
		$this->setPermission("pc.command.resetstats");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.resetstats")){
			$player->sendMessage("§cYou cannot execute this command.");
			return;
		}
		if(!isset($args[0])){
			$player->sendMessage("§cYou must provide a player.");
			return;
		}
		$target=$this->plugin->getServer()->getPlayer($args[0]);
        $player->sendMessage("§aYou reset ".$args[0]."'s stats.");
        Utils::resetStats($args[0]);
		if($target instanceof Player){
			$target->sendMessage("§aYour stats have been reset.");
		}
	}
}