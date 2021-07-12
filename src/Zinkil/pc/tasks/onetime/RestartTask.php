<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks\onetime;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;

class RestartTask extends Task{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onRun(int $currentTick):void{
		$count=count($this->plugin->getServer()->getOnlinePlayers());
		if($count > 0){
			foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
				if($this->plugin->getDuelHandler()->isInDuel($player)){
					$duel=$this->plugin->getDuelHandler()->getDuel($player);
					$duel->endDuelPrematurely(true);
				}
				$player->transfer("198.50.158.171", 19132, "§bPractice is restarting.");
			}
		}else{
			$this->plugin->getServer()->shutdown();
		}
	}
}