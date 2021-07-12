<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\Task;
use Zinkil\pc\Core;

class MutesTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onRun(int $tick):void{
		$query=$this->plugin->staff->query("SELECT * FROM mutes ORDER BY duration ASC;");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		$now=time();
		if(!empty($result)){
			$players=$result['player'];
			$duration=$result['duration'];
			if($duration<=$now){
				$target=Core::getInstance()->getServer()->getPlayerExact($players);
				if($target!==null){
					$this->plugin->staff->exec("DELETE FROM mutes WHERE player='".$target->getName()."';");
					$target->sendMessage("§aYour mute has expired.");
					}else{
						$this->plugin->staff->exec("DELETE FROM mutes WHERE player='".$players."';");
				}
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("autounmute");
				$message=str_replace("{target}", $players, $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
			}
		}
	}
}