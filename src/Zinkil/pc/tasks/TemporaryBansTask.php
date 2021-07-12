<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\Task;
use Zinkil\pc\Core;

class TemporaryBansTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onRun(int $tick):void{
		$query=$this->plugin->staff->query("SELECT * FROM temporarybans ORDER BY duration ASC;");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		$now=time();
		if(!empty($result)){
			$players=$result['player'];
			$duration=$result['duration'];
			if($duration<=$now){
				$this->plugin->getLogger()->notice("Unbanned ".$players.". Ban time has expired.");
				$this->plugin->staff->exec("DELETE FROM temporarybans WHERE player='".$players."';");
				$message=$this->plugin->getStaffUtils()->sendStaffNoti("autotemporaryunban");
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