<?php

declare(strict_types=1);

namespace Zinkil\pc\data;

use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;
use Zinkil\pc\tasks\MysqlTask;

class Provider{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function open(){
		$this->mysql=new \mysqli(Core::HOST, Core::USER, Core::PASS, Core::DATABASE, 3306);
		if($this->mysql->connect_error){
			Core::getInstance()->getLogger()->critical("Could not connect to MySQL server: " . $this->msql->connect_error);
			return;
		}
		Core::getInstance()->getLogger()->notice("Database connection successful.");
	}
	public function close(){
		if($this->mysql instanceof \mysqli){
			$this->mysql->close();
		}
	}
	
	public function rankExists($player):bool{
		if($player instanceof Player) $player=$player->getName();
		if(!$result=$this->mysql->query("SELECT rank, COUNT(*) as count FROM rank WHERE player='".$this->mysql->real_escape_string($player)."'")){
			Core::getInstance()->getLogger()->critical($this->mysql->error);
			return false;
		}
		$res=$result->fetch_array();
		$result->free();
		return $res['count'] >= 1;
	}
	public function rankCreate($player){
		$rank="Player";
		if($player instanceof Player) $player=$player->getName();
		if(!$this->rankExists($player)){
			Core::getInstance()->getLogger()->notice("Creating rank row...");
			if(!$this->mysql->query("INSERT INTO rank (player, rank) VALUES ('".$this->mysql->real_escape_string($player)."', '".$rank."');")){
				Core::getInstance()->getLogger()->critical($this->mysql->error);
				return;
			}
		}
	}
	public function getRank($player){
		if($player instanceof Player) $player=$player->getName();
		if(!$result=$this->mysql->query("SELECT rank FROM rank WHERE player='".$this->mysql->real_escape_string($player)."'")){
			Core::getInstance()->getLogger()->critical($this->mysql->error);
			return "Player";
		}
		$res=$result->fetch_array()[0] ?? false;
		$result->free();
		return $res;
	}
	public function setRank($player, $rank){
		if($player instanceof Player) $player=$player->getName();
		if($player instanceof CPlayer) $player->setRank($rank);
		return $this->mysql->query("UPDATE rank SET rank='".$rank."' WHERE player='".$this->mysql->real_escape_string($player)."'");
	}
	public function countWithRank($rank){
		if(!$result=$this->mysql->query("SELECT rank, COUNT(*) as count FROM rank WHERE rank='$rank'")){
			Core::getInstance()->getLogger()->critical($this->mysql->error);
			return 0;
		}
		$res=$result->fetch_array();
		$result->free();
		return $res['count'];
	}
	
	public function temporaryRankExists($player):bool{
		if($player instanceof Player) $player=$player->getName();
		if(!$result=$this->mysql->query("SELECT temporaryrank, COUNT(*) as count FROM temporaryrank WHERE player='".$this->mysql->real_escape_string($player)."'")){
			Core::getInstance()->getLogger()->critical($this->mysql->error);
			return false;
		}
		$res=$result->fetch_array();
		$result->free();
		return $res['count'] >= 1;
	}
	public function temporaryRankCreate($player, $temp, $duration, $original){
		if($player instanceof Player) $player=$player->getName();
		if(!$this->temporaryRankExists($player)){
			if(!$this->mysql->query("INSERT INTO temporaryrank (player, temporaryrank, duration, originalrank) VALUES ('".$this->mysql->real_escape_string($player)."', '".$temp."', '".$duration."', '".$original."');")){
				Core::getInstance()->getLogger()->critical($this->mysql->error);
				return;
			}
		}
	}
	public function voteAccessExists($player):bool{
		if($player instanceof Player) $player=$player->getName();
		if(!$result=$this->mysql->query("SELECT duration, COUNT(*) as count FROM voteaccess WHERE player='".$this->mysql->real_escape_string($player)."'")){
			Core::getInstance()->getLogger()->critical($this->mysql->error);
			return false;
		}
		$res=$result->fetch_array();
		$result->free();
		return $res['count'] >= 1;
	}
	public function voteAccessCreate($player, $duration){
		if($player instanceof Player) $player=$player->getName();
		if(!$this->voteAccessExists($player)){
			if(!$this->mysql->query("INSERT INTO voteaccess (player, duration) VALUES ('".$this->mysql->real_escape_string($player)."', '".$duration."');")){
				Core::getInstance()->getLogger()->critical($this->mysql->error);
				return;
			}
		}
	}
}