<?php

declare(strict_types=1);

namespace Zinkil\pc\duels\groups;

use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class QueuedPlayer{
	
	private $name;
	private $queue;
	private $ranked;
	private $pe;
	
	public function __construct(string $name, string $queue, bool $ranked=false, bool $pe=false){
		$this->name=$name;
		$this->queue=$queue;
		$this->ranked=$ranked;
		$this->pe=$pe;
	}
	public function getPlayerName():string{
		return $this->name;
	}
	public function getQueue():string{
		return $this->queue;
	}
	public function isRanked():bool{
		return $this->ranked;
	}
	public function isPe():bool{
		return $this->pe;
	}
	public function getPlayer(){
		return Utils::getPlayer($this->name);
	}
	public function isPlayerOnline():bool{
		return !is_null($this->getPlayer()) and $this->getPlayer()->isOnline();
	}
	public function hasSameQueue(QueuedPlayer $player):bool{
		$result=false;
		if($player->getQueue()===$this->queue){
			$ranked=$player->isRanked();
			$result=$this->ranked===$ranked;
		}
		return $result;
	}
	public function equals($object):bool{
		$result=false;
		if($object instanceof QueuedPlayer){
			if($object->getPlayerName()===$this->name){
				$result=true;
			}
		}
		return $result;
	}
}