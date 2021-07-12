<?php

declare(strict_types=1);

namespace Zinkil\pc\duels;

use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\plugin\ScriptPluginLoader;
use Zinkil\pc\duels\PracticeArena;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class DuelArena extends PracticeArena{
	
	private $center;
	
	private $playerPos;
	private $opponentPos;
	
	private $playerPitch;
	private $playerYaw;
	
	private $oppPitch;
	private $oppYaw;
	
	public function __construct(string $name, bool $canBuild, Position $center, Position $playerPos=null, Position $oppPos=null, $modes){
		parent::__construct($name, self::DUEL_ARENA, $canBuild, $center);
		$this->modes=$modes;
		$this->playerPos=new Position($center->x - 6, $center->y, $center->z, $center->level);
		$this->opponentPos=new Position($center->x + 6, $center->y, $center->z, $center->level);
		$this->centerPos=new Position($center->x, $center->y, $center->z, $center->level);
		$this->playerPitch=null;
		$this->playerYaw=null;
		$this->oppYaw=null;
		$this->oppPitch=null;
		if(!is_null($playerPos)){
			$this->playerPos=$playerPos;
			if($playerPos instanceof Location){
				$this->playerPitch=$playerPos->pitch;
				$this->playerYaw=$playerPos->yaw;
			}
		}
		if(!is_null($oppPos)){
			$this->opponentPos=$oppPos;
			if($oppPos instanceof Location){
				$this->oppPitch=$oppPos->pitch;
				$this->oppYaw=$oppPos->yaw;
			}
		}
	}
	public function getModes():array{
		return $this->modes;
	}
	public function setPlayerPos($pos):DuelArena{
		if($pos instanceof Position){
			if(Utils::areLevelsEqual($pos->level, $this->level)){
				$this->playerPos=$pos;
			}
		} elseif ($pos instanceof Location){
			if(Utils::areLevelsEqual($pos->level, $this->level)){
				$this->playerPos=new Position($pos->x, $pos->y, $pos->z, $pos->level);
				$this->playerYaw=$pos->yaw;
				$this->playerPitch=$pos->pitch;
			}
		}
		return $result;
	}
	public function setOpponentPos($pos):DuelArena{
		if($pos instanceof Position){
			if(Utils::areLevelsEqual($pos->level, $this->level)){
				$this->opponentPos=$pos;
			}
		} elseif ($pos instanceof Location){
			if(Utils::areLevelsEqual($pos->level, $this->level)){
				$this->opponentPos=new Position($pos->x, $pos->y, $pos->z, $pos->level);
				$this->oppYaw=$pos->yaw;
				$this->oppPitch=$pos->pitch;
			}
		}
		return $result;
	}
	public function getPlayerPos(){
		$result=$this->playerPos;
		if(!is_null($this->playerYaw) and !is_null($this->playerPitch)){
			$result=new Location($this->playerPos->x, $this->playerPos->y, $this->playerPos->z, $this->playerYaw, $this->playerPitch, $this->level);
		}
		return $result;
	}
	public function getOpponentPos(){
		$result=$this->opponentPos;
		if(!is_null($this->oppYaw) and !is_null($this->oppPitch)){
			$result=new Location($this->opponentPos->x, $this->opponentPos->y, $this->opponentPos->z, $this->oppYaw, $this->oppPitch, $this->level);
		}
		return $result;
	}
	public function getCenterPos(){
		$result=$this->centerPos;
		$result=new Position($this->centerPos->x, $this->centerPos->y, $this->centerPos->z, $this->level);
		return $result;
	}
}