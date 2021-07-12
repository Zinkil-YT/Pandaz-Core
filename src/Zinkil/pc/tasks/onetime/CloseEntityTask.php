<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks\onetime;

use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class CloseEntityTask extends Task{
	
	private $entity;
	
	public function __construct(Core $plugin, Entity $entity){
		$this->plugin=$plugin;
		$this->entity=$entity;
	}
	public function onRun(int $currentTick):void{
		if(!$this->entity->isClosed()){
			$this->entity->close();
		}
	}
}