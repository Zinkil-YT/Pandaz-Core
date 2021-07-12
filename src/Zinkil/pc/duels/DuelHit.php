<?php

declare(strict_types=1);

namespace Zinkil\pc\duels;

class DuelHit{
	
	private $player;
	private $tick;
	
	public function __construct(string $player, int $tick){
		$this->player=$player;
		$this->tick=$tick;
	}
	public function getPlayer():string{
		return $this->player;
	}
	public function getTick():int{
		return $this->tick;
	}
	public function equals($object):bool{
		$result=false;
		if($object instanceof DuelHit){
			$result=$this->tick===$object->getTick();
		}
		return $result;
	}
}