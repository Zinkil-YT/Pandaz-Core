<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use Zinkil\pc\Core;

class TemporaryRankTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onRun(int $tick):void{
		$query=$this->plugin->main->query("SELECT * FROM temporaryranks ORDER BY duration ASC;");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		$now=time();
		if(!empty($result)){
			$players=$result['player'];
			$duration=$result['duration'];
			$temporaryrank=$result['temprank'];
			$originalrank=$result['oldrank'];
			if($now>=$duration){
				$target=Core::getInstance()->getServer()->getPlayerExact($players);
				if($this->plugin->getDatabaseHandler()->getRank($players)==$temporaryrank){
					if(is_null($originalrank)){
						$this->plugin->getDatabaseHandler()->setRank($target, "Player");
					}else{
						$this->plugin->getDatabaseHandler()->setRank($target, $originalrank);
					}
				}
				$this->plugin->main->query("DELETE FROM temporaryranks WHERE player='".$players."'");
				if($target instanceof Player) $target->sendMessage("§cYour ".$temporaryrank." rank has expired.");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankexpire");
				$message=str_replace("{target}", $players, $message);
				$message=str_replace("{rank}", "Voter", $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
			}
		}
	}
}