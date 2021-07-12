<?php

declare(strict_types=1);

namespace Zinkil\pc\handlers;

use pocketmine\Player;
use pocketmine\Server;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class ClickHandler{
	
	private $plugin;
	private $clicks=[];
	
	public function __construct(){
		$this->plugin=Core::getInstance();
	}
	public function isInArray($player):bool{
		$name=Utils::getPlayerName($player);
		return ($name !== null) and isset($this->clicks[$name]);
	}
	public function addToArray(Player $player){
		if(!$this->isInArray($player)){
			$this->clicks[$player->getName()]=[];
		}
	}
	public function removeFromArray(Player $player){
		if($this->isInArray($player)){
			unset($this->clicks[$player->getName()]);
		}
	}
	public function addClick(Player $player){
		array_unshift($this->clicks[$player->getName()], microtime(true));
		if(count($this->clicks[$player->getName()]) >= 100){
			array_pop($this->clicks[$player->getName()]);
		}
		if(Utils::isCpsCounterEnabled($player)==true) $player->sendTip("\n\n\n\n§b§lCPS §8: §f".$this->getCps($player));
	}
	public function getCps(Player $player, float $deltaTime=1.0, int $roundPrecision=1):float{
		if(!$this->isInArray($player) or empty($this->clicks[$player->getName()])){
			return 0.0;
		}
		$mt=microtime(true);
		return round(count(array_filter($this->clicks[$player->getName()], static function(float $t) use ($deltaTime, $mt):bool{
			return ($mt - $t) <= $deltaTime;
			})) / $deltaTime, $roundPrecision);
	}
}