<?php

declare(strict_types=1);

namespace Zinkil\pc\duels\groups;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\Bed;
use pocketmine\block\Liquid;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\tile\Tile;
use pocketmine\tile\Bed as TileBed;
use pocketmine\Player;
use pocketmine\Server;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;
use Zinkil\pc\Kits;
use Zinkil\pc\duels\groups\MatchedBotGroup;

class BotDuelGroup{
	
	public const NONE="None";
	
	public const MAX_COUNTDOWN_SEC=7;
	public const MAX_DURATION_MIN=10;
	public const MAX_END_DELAY_SEC=5;
	
	private $plugin;
	private $playerName;
	private $botName;
	private $bot;
	private $arenaName;
	private $winnerName;
	private $loserName;
	
	private $difficulty;
	private $started;
	private $ended;
	private $spectators;
	private $arena;
	
	private $currentTick;
	private $countdownTick;
	private $endTick;

	public function __construct(MatchedBotGroup $group, string $arena){
		$this->plugin=Core::getInstance();
		$this->playerName=$group->getPlayerName();
		$this->botName=$group->getBotName();
		$this->bot=$group->getBot();
		
		$this->winnerName=self::NONE;
		$this->loserName=self::NONE;
		$this->arenaName=$arena;
		
		$this->difficulty=$group->getDifficulty();
		
		$player=$group->getPlayer();
		$p=$player->getPlayer();
		
		$bot=$group->getBot();
		
		$this->fightingTick=0;
		$this->currentTick=0;
		$this->countdownTick=0;
		$this->endTick=-1;
		
		$this->started=false;
		$this->ended=false;
		
		$this->spectators=[];
		
		$this->maxCountdownTicks=Utils::secondsToTicks(self::MAX_COUNTDOWN_SEC);
		
		$this->arena=$this->plugin->getArenaHandler()->getDuelArena($arena);
	}
	
	public function getDifficulty():string{ return $this->difficulty; }
	
	public function isEasy():bool{ return ucfirst($this->difficulty)=="Easy"; }
	
	public function getArena(){ return $this->arena; }
	
	public function isPlayer($player):bool{
		$result=false;
		$p=Utils::getPlayer($player);
		$name=Utils::getPlayerName($p);
		$result=$name===$this->playerName;
		return $result;
	}
	public function isBot($player):bool{
		$result=false;
		$result=$player===$this->botName;
		return $result;
	}
	public function getPlayer(){
		return Utils::getPlayer($this->playerName);
	}
	public function getBot(){
		return $this->bot;
	}
	public function getPlayerName():string{
		return $this->playerName;
	}
	public function getBotName():string{
		return $this->botName;
	}
	public function getArenaName():string{
		return $this->arenaName;
	}
	public function isDuelRunning():bool{
		return $this->started===true and $this->ended===false;
	}
	public function isLoadingDuel():bool{
		return $this->started===false and $this->ended===false;
	}
	public function didDuelEnd():bool{
		return $this->started===true and $this->ended===true;
	}
	
	public function update():void{
		if(!$this->isPlayerOnline()){
			$this->endDuel();
			return;
		}
		//if(!$this->isBotOnline() or $this->getBot()===null or $this->getBot()->getLevel()===null){
		if(!$this->isBotOnline() or $this->getBot()===null){
			$this->setResults($this->playerName, $this->botName);
			//return;
		/*}elseif(!$this->arePlayersOnline()){
			$this->endDuelPrematurely();
			return;*/
		}
		$arena=$this->getArena();
		$arenaname=$this->getArenaName();
		$duellevel=$arena->getLevel();
		$player=$this->getPlayer();
		$bot=$this->getBot();
		if($this->isLoadingDuel()){
			if($this->countdownTick===0) $this->initializePlayers(0);
			$this->countdownTick++;
			if($this->countdownTick % 20 === 0 and $this->countdownTick !== 0){
				$second=self::MAX_COUNTDOWN_SEC - Utils::ticksToSeconds($this->countdownTick);
				
				$this->initializePlayers(0);
				if($player->getLevel()->getName()!=$duellevel->getName()){
					$player->teleport($arena->getPlayerPos());
				}
				if($bot->getLevel()->getName()!=$duellevel->getName()){
					$bot->teleport($arena->getOpponentPos());
				}
				if($second !== 0){
					if(6 > $second){
						$this->broadcastSound(0);
						$this->initializePlayers(0);
						$this->broadcastMessage("§fThe match will start in §b".$second."§f seconds...");
						$this->broadcastTitle("§b".$second);
					}
				}else{
					$this->broadcastSound(1);
					$this->initializePlayers(1);
					$this->broadcastMessage("§fThe match has started!");
					$this->broadcastTitle("§l§bDUEL!", "§r§fThe match has started", 5, 10, 8);
				}
			}
			if($this->countdownTick >= $this->maxCountdownTicks) $this->start();
		}elseif($this->isDuelRunning()){
			$duration=$this->getDuration();
			if($this->fightingTick > 0){
				$this->fightingTick--;
				if(0 >= $this->fightingTick){
					$this->fightingTick=0;
				}
			}
			$duration=$this->getDuration();
			$maxDuration=Utils::minutesToTicks(self::MAX_DURATION_MIN);
			if($duration % 20 === 0){
				$this->updateScoreboards();
			}
			if($this->isPlayerBelowCenter($player, 10.0) and $this->isDuelRunning()){
				$this->setResults($this->botName, $this->playerName);
				return;
			}
			if($this->isBotBelowCenter($bot, 10.0) and $this->isDuelRunning()){
				$this->setResults($this->playerName, $this->botName);
				return;
			}
			if($duration >= $maxDuration){
				$this->endDuel();
				$this->broadcastMessage("§cThe match duration has exceeded the limit, now ending.");
				return;
			}
		}else{
			$difference=$this->currentTick - $this->endTick;
			$seconds=Utils::ticksToSeconds($difference);
			if($seconds >= self::MAX_END_DELAY_SEC)
			$this->endDuel(false, false);
		}
		$this->currentTick++;
	}
	public function setResults($winner=self::NONE, $loser=self::NONE){
		if($this->didDuelEnd()) return;
		$this->winnerName=$winner;
		$this->loserName=$loser;
		if($winner!==self::NONE and $loser!==self::NONE){
			$player=Utils::getPlayer($winner);
			$Lplayer=Utils::getPlayer($loser);
			if($this->winnerName==$this->playerName){
				Server::getInstance()->broadcastMessage("§b".Utils::getPlayerDisplayName($this->winnerName)." won a match against the ".ucfirst($this->difficulty)." Bot!");
			}else{
				//Server::getInstance()->broadcastMessage("§b".Utils::getPlayerDisplayName($this->winnerName)." lost a match to the ".ucfirst($this->difficulty)." Bot!");
			}
			if($player instanceof CPlayer) $this->initializeWin($player);
			if($Lplayer instanceof CPlayer) $this->initializeLoss($Lplayer);
		}
		$this->setDuelEnded();
	}
	private function endDuel(bool $endPrematurely=false, bool $disablePlugin=false):void{
		$this->clearSpectators();
		$this->setDuelEnded();
		$player=Utils::getPlayer($this->playerName);
		$winner=Utils::getPlayer($this->winnerName);
		$loser=Utils::getPlayer($this->loserName);
		if($player instanceof CPlayer) $player->sendTo(0, true);
		if($player instanceof CPlayer) $player->setTagged(false);
		Utils::clearEntities($this->arena->getLevel(), true, true);
		$this->plugin->getDuelHandler()->endBotDuel($this);
		$this->plugin->getArenaHandler()->setArenaOpen($this->arenaName);
	}
	public function endDuelPrematurely(bool $disablePlugin=false):void{
		$premature=true;
		if($disablePlugin===true){
			$this->setDuelEnded();
		}
		if($disablePlugin===false){
			if($this->isDuelRunning() or $this->didDuelEnd()){
				$premature=false;
			}
		}
		$this->endDuel($premature, $disablePlugin);
	}
	private function setDuelEnded(bool $result=true){
		//if($this->didDuelEnd()) return;
		$this->ended=$result;
		$this->endTick=$this->endTick == -1 ? $this->currentTick : $this->endTick;
		if($this->isBotOnline()) $this->getBot()->setDeactivated(true);
	}
	private function start(){
		$this->started=true;
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			$player->setImmobile(false);
		}
	}
	private function updateScoreboards():void{
		$duration=$this->getDurationString();
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			$this->plugin->getScoreboardHandler()->updateBotDuelDuration($player, $duration);
		}
	}
	public function broadcastMessage(string $message):void{
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			$player->sendMessage($message);
		}
	}
	public function broadcastTitle(string $title, string $subtitle="", int $in=0, int $stay=40, int $out=0):void{
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			$player->addTitle($title, $subtitle, $in, $stay, $out);
		}
	}
	public function broadcastSound(int $type):void{
		switch($type){
			case 0:
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				Utils::clickSound($player);
			}
			break;
			case 1:
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				//Utils::shootSound($player);
			}
			break;
			default:
			return;
			break;
		}
	}
	public function initializePlayers(int $type):void{
		switch($type){
			case 0:
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				$player->setImmobile(true);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
				$player->setFood(20);
				$player->setHealth(20);
			}
			if($this->isBotOnline()){
				if($this->isBotOnline()){
				if($this->isBotOnline()) $this->getBot()->setDeactivated(false);
				if($this->isBotOnline()) Kits::sendMatchKit($this->getBot(), "NoDebuff");
				if($this->isBotOnline()) $this->getBot()->getInventory()->setItemInHand(Item::get(Item::DIAMOND_SWORD, 0, 1));
				if($this->isBotOnline()) $this->getBot()->getInventory()->sendHeldItem($this->getBot()->getViewers());
			}
			break;
			case 1:
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				$player->setImmobile(false);
				Kits::sendMatchKit($player, "NoDebuff");
			}
			if($this->isBotOnline()){
				if($this->isBotOnline()) $this->getBot()->setDeactivated(false);
			}
			break;
			default:
			return;
			break;
		}
	}
	public function initializeWin($player):void{
		if(Utils::isPlayer($player)){
			if(!is_null($player)){
				$player->setImmobile(false);
				$player->addTitle("§l§aVICTORY", "§r§eYou won", 10, 30, 40);
			}
		}
	}
	public function initializeLoss($player):void{
		if(Utils::isPlayer($player)){
			if(!is_null($player)){
				$player->setImmobile(false);
				$player->addTitle("§l§cDEFEAT", "§r§eYou lost", 10, 30, 40);
				$player->setGamemode(3);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
			}
		}
	}
	public function clearSpectators(){
		if(empty($this->spectators)) return;
		foreach(Server::getInstance()->getOnlinePlayers() as $spectators){
			if($this->isSpectator($spectators)){
				$specs=Utils::getPlayer($spectators);
				$specs->sendMessage("§aThe duel has ended, sending you back to the lobby.");
				$this->spectators=[];
				$specs->sendTo(0, true);
			}
		}
	}
	private function isPlayerBelowCenter(Player $player, float $below):bool{
		$y=$player->getY();
		$arena=$this->getArena();
		$centerY=$arena->getCenterPos()->y;
		return $y + $below <= $centerY;
    }
    private function isBotBelowCenter($bot, float $below):bool{
		if(!$this->isBotOnline()) return true;
		$y=$bot->getY();
		$arena=$this->getArena();
		$centerY=$arena->getCenterPos()->y;
		return $y + $below <= $centerY;
    }
    public function arePlayersOnline():bool{
		$result=false;
		$bt=$this->getBot();
		$pl=$this->getPlayer();
		$result=$bt->isAlive() and $pl->isOnline();
		return $result;
	}
	public function isPlayerOnline():bool{
		$result=false;
		if(Utils::isPlayer($this->playerName)){
			$player=$this->getPlayer();
			$result=$player->isOnline();
		}
		return $result;
	}
	public function isBotOnline():bool{
		$result=false;
		$bot=$this->getBot();
		return $bot->isAlive() and $bot!==null;
	}
	public function getDuration():int{
		$duration=$this->currentTick - $this->countdownTick;
		if($this->didDuelEnd()){
			$endTickDiff=$this->currentTick - $this->endTick;
			$duration=$duration - $endTickDiff;
		}
		return $duration;
	}
	public function getDurationString():string{
		$s="mm:ss";
		$seconds=Utils::ticksToSeconds($this->getDuration());
		$minutes=Utils::ticksToMinutes($this->getDuration());
		if($minutes > 0){
			if(10 > $minutes){
				$s=Utils::str_replace($s, ['mm' => '0' . $minutes]);
			}else{
				$s=Utils::str_replace($s,  ['mm' => $minutes]);
			}
		}else{
			$s=Utils::str_replace($s,  ['mm' => '00']);
		}
		$seconds=$seconds % 60;
		if($seconds > 0){
			if(10 > $seconds){
				$s=Utils::str_replace($s, ['ss' => '0' . $seconds]);
			}else{
				$s=Utils::str_replace($s, ['ss' => $seconds]);
			}
		}else{
			$s=Utils::str_replace($s, ['ss' => '00']);
		}
		return $s;
	}
	public function isSpectator($player):bool{
		$name=Utils::getPlayerName($player);
		return($name !== null) and isset($this->spectators[$name]);
	}
	public function addSpectator($spectator):void{
		$p=Utils::getPlayer($spectator);
		if($this->plugin->getDuelHandler()->isInDuel($p)) return;
		$spectator=new DuelSpectator($p);
		$center=$this->getArena()->getSpawnPosition();
		$spectator->teleport($center);
		$name=Utils::getPlayerName($p);
		//$this->spectators[$name]=$spectator;
		
		$player=$this->getPlayer();
		$playerDS=Utils::getPlayerDisplayName($player);
		
		Kits::sendKit($p, "spectator");
		$p->sendMessage("§aYou are now spectating.");
		$player->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
		foreach(Server::getInstance()->getOnlinePlayers() as $spectators){
			if($this->isSpectator($spectators)){
				if($spectators!==null){
					$spectators->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
				}
			}
		}
		$this->spectators[Utils::getPlayerName($p)]=[];
		/*if($this->ranked===true){
			$this->plugin->getScoreboardHandler()->sendDuelSpectateScoreboard($p, "Ranked", $this->queue, $playerDS, $this->botName);
		}else{
			$this->plugin->getScoreboardHandler()->sendDuelSpectateScoreboard($p, "Unranked", $this->queue, $playerDS, $this->botName);
		}*/
	}
	public function removeSpectator($spectator, $send=false):void{
		if($this->isSpectator($spectator)){
			$p=Utils::getPlayer($spectator);
			unset($this->spectators[Utils::getPlayerName($p)]);
			
			$player=$this->getPlayer();
			$playerDS=Utils::getPlayerDisplayName($player);
			
			if($send===true){
				$p->sendTo(0, true);
			}
			
			$p->sendMessage("§aYou are no longer spectating.");
			$player->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis no longer spectating.");
			foreach(Server::getInstance()->getOnlinePlayers() as $spectators){
				if($this->isSpectator($spectators)){
					if($spectators!==null){
						$spectators->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis no longer spectating.");
					}
				}
			}
		}
	}
	private function getSpectators():array{
		$result=[];
		$keys=array_keys($this->spectators);
		foreach($keys as $key){
			$name=strval($key);
			$spec=$this->spectators[$name];
			if($spec!==null){
				if(Utils::getPlayer($spec)!==null){
					$result[]=$spec;
				}else{
					unset($this->spectators[$key]);
				}
			}
		}
		return $result;
	}
}
