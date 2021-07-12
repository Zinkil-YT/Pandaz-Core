<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\Task;
use Zinkil\pc\Core;

class MotdTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
		$this->line=-1;
	}
	public function onRun(int $tick):void{
		$motd=[
		"§l§bPANDAZ » §3Best WW",
		"§l§eNEW » §dBot Duels",
		"§l§cEU » §fPractice"
		];
		$this->line++;
		$msg=$motd[$this->line];
		$this->plugin->getServer()->getNetwork()->setName($msg);
		if($this->line===count($motd) - 1){
			$this->line = -1;
		}
	}
}