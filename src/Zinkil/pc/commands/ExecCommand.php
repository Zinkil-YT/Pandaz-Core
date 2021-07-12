<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class ExecCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("exec", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bExecute database commands");
		$this->setPermission("pc.command.exec");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if($player instanceof Player){
			return;
		}
		if(!$player->hasPermission("pc.command.exec")){
			return;
		}
		Utils::offerVoteRewards(implode(" ", $args));
	}
}