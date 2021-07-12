<?php

declare(strict_types=1);

namespace Zinkil\pc\handlers;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\utils\Config;
use Zinkil\pc\duels\PracticeArena;
use Zinkil\pc\duels\DuelArena;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\Kits;

class ArenaHandler{
	
	private $plugin;
	private $configPath;
	private $config;
	private $closedArenas;
	private $duelArenas;
	
	public function __construct(){
		$this->plugin=Core::getInstance();
		$this->configPath=$this->plugin->getDataFolder()."/arenas.yml";
		$this->initConfig();
		$this->closedArenas=[];
		$this->initArenas();
		$combined_arrays=array_merge($this->getDuelArenas());
		foreach($combined_arrays as $value){
			if($value instanceof PracticeArena){
				$name=$value->getName();
				$this->closedArenas[$name]=false;
			}
		}
	}
	private function initConfig():void{
		$this->config=new Config($this->configPath, Config::YAML, array());
		$edited=false;
		if(!$this->config->exists("duel-arenas")){
			$this->config->set("duel-arenas", []);
			$edited=true;
		}
		if($edited === true) $this->config->save();
	}
private function initArenas():void{
        $this->duelArenas=[];
        $duelKeys=$this->getConfig()->get("duel-arenas");
        $duelArenaKeys=array_keys($duelKeys);
        foreach($duelArenaKeys as $key){
            $key=strval($key);
            if($this->isDuelArenaFromConfig($key)){
                $arena=$this->getDuelArenaFromConfig($key);
                $this->duelArenas[$key]=$arena;
            }
        }
    }
	private function getConfig():Config{
		return $this->config;
	}
	public function getDuelArena(string $name){
		$result=null;
		if(isset($this->duelArenas[$name])){
			$result=$this->duelArenas[$name];
		}
		return $result;
	}
	private function getDuelArenaFromConfig(string $name){
		$duelArenas=$this->getConfig()->get("duel-arenas");
		$result=null;
		if(isset($duelArenas[$name])){
			$arena=$duelArenas[$name];
			$arenaCenter=null;
			$playerPos=null;
			$oppPos=null;
			$build=false;
			$modes=[];
			$foundArena=false;
			if(Utils::arr_contains_keys($arena, "center", "build", "level", "player-pos", "opponent-pos", "modes")){
				$canBuild=$arena["build"];
				$centerArr=$arena["center"];
				$level=$arena["level"];
				$cfgModes=$arena["modes"];
				$cfgPlayerPos=$arena["player-pos"];
				$cfgOppPos=$arena["opponent-pos"];
				$arenaCenter=Utils::getPositionFromMap($centerArr, $level);
				$playerPos=Utils::getPositionFromMap($cfgPlayerPos, $level);
				$oppPos=Utils::getPositionFromMap($cfgOppPos, $level);
				if(is_bool($canBuild)) $build=$canBuild;
				if(!is_null($arenaCenter) and !is_null($playerPos) and !is_null($oppPos)) $foundArena=true;
			}
			if($foundArena){
				$result=new DuelArena($name, $build, $arenaCenter, $playerPos, $oppPos, $cfgModes);
			}
		}
		return $result;
	}
	private function isDuelArenaFromConfig(string $name):bool{
		return !is_null($this->getDuelArenaFromConfig($name));
	}
	public function isDuelArena(string $name):bool{
		return isset($this->duelArenas[$name]);
	}
	public function getArena(string $name){
		$result=null;
		if($this->isDuelArena($name)){
			$result=$this->getDuelArena($name);
		}
		return $result;
	}
	public function doesArenaExist(string $name):bool{
		return $this->isDuelArena($name);
	}
	public function setArenaClosed($arena):void{
		$name=null;
		if(isset($arena) and !is_null($arena)){
			if($arena instanceof PracticeArena){
				$name=$arena->getName();
			} elseif(is_string($arena)){
				$name=$arena;
			}
		}
		if(!is_null($name)){
			if(!$this->isArenaClosed($name)){
				$this->closedArenas[$name]=true;
			}
		}
	}
	public function isArenaClosed(string $arena):bool{
		return isset($this->closedArenas[$arena]) and $this->closedArenas[$arena] === true;
	}
	public function setArenaOpen($arena):void{
		$name=null;
		if(isset($arena) and !is_null($arena)){
			if($arena instanceof PracticeArena){
				$name=$arena->getName();
			} elseif(is_string($arena)){
				$name=$arena;
			}
		}
		if(!is_null($name)){
			if($this->isArenaClosed($name)){
				$this->closedArenas[$name]=false;
			}
		}
	}
	public function getDuelArenas():array{
		return $this->duelArenas;
	}
	public function getArenaClosestTo(Position $pos){
		$arenas=$this->getDuelArenas();
		$greatest=null;
		$closestDistance=1.0;
		foreach($arenas as $arena){
			if($arena instanceof PracticeArena){
				if($pos->getLevel()->getName()==$arena->getLevel()->getName()){
					$center=$arena->getSpawnPosition();
					$currentDistance=$center->distance($pos);
					if($closestDistance<200){
						$greatest=$arena;
						//$this->plugin->getServer()->broadcastMessage("Arena found");
					}
				}
			}
		}
		return $greatest;
	}
}