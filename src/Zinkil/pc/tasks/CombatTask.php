<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\Task;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class CombatTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onRun(int $currentTick):void{
		foreach($this->plugin->taggedPlayer as $name => $time) {
			$player=$this->plugin->getServer()->getPlayerExact($name);
			$time--;
			if($player->isTagged()){
				if(Utils::isCombatCounterEnabled($player)==true) $player->sendPopup("§l§cCombat §8: §f".$time);
			}
			if($time<=0){
				$player->setTagged(false);
				if(Utils::isCombatCounterEnabled($player)==true) $player->sendPopup("§l§aYou can logout now");
				return;
			}
			$this->plugin->taggedPlayer[$name]--;
		}
	}
}