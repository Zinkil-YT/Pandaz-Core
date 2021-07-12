<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\Task;
use Zinkil\pc\Core;

class BroadcastTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
		$this->line=-1;
	}
	public function onRun(int $tick):void{
		$cast=[
		$this->plugin->getCastPrefix()."Join our official discord at ".$this->plugin->getDiscord().".",
		$this->plugin->getCastPrefix()."If you want a Youtube rank (300+) or a Famous rank (800+) make a video on server and tell me in discord server ".$this->plugin->getDiscord().".",
		$this->plugin->getCastPrefix()."Check out our twitter, ".$this->plugin->getTwitter().".",
		$this->plugin->getCastPrefix()."Buy a rank for access to exlusive features at ".$this->plugin->getStore()."."
		];
		$this->line++;
		$msg=$cast[$this->line];
		foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
			$online->sendMessage($msg);
		}
		if($this->line===count($cast) - 1) $this->line = -1;
	}
}