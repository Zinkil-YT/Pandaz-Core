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
use pocketmine\item\SplashPotion as ItemSplashPotion;
use pocketmine\Player;
use pocketmine\Server;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;
use Zinkil\pc\Kits;
use Zinkil\pc\duels\DuelHit;
use Zinkil\pc\duels\groups\MatchedGroup;
use Zinkil\pc\duels\groups\DuelSpectator;
use Zinkil\pc\tasks\onetime\OpenArenaTask;
use Zinkil\pc\tasks\onetime\LoadLevelTask;
use Zinkil\pc\forms\{SimpleForm, ModalForm, CustomForm};
use Zinkil\pc\party\Party;

class PartyDuelGroup{
	
	public const NONE="None";
	
	public const MAX_COUNTDOWN_SEC=7;
	public const MAX_DURATION_MIN=1;
	public const MAX_END_DELAY_SEC=5;
	
	private $party;
	private $players=[];
	private $killed=[];
	private $alive=0;
	private $winnerName;
	
	private $queue;
	private $allowspecs;
	private $arena;

	private $started;
	private $ended;
	private $spectators;
	
	private $currentTick;
	private $countdownTick;
	private $endTick;

	public function __construct(Party $party, array $players, string $queue, bool $allowspecs, string $arena){
		$this->party=$party;
		$this->players=$players;
		$this->killed=[];
		$this->alive=count($players);
		$this->winnerName=self::NONE;
		$this->arenaName=$arena;
		
		$this->queue=$queue;
		$this->allowspecs=$allowspecs;
		
		$this->maxCountdownTicks=Utils::secondsToTicks(self::MAX_COUNTDOWN_SEC);
		
		$this->fightingTick=0;
		$this->currentTick=0;
		$this->countdownTick=0;
		$this->endTick=-1;
		
		$this->started=false;
		$this->ended=false;
		
		$this->spectators=[];
		$this->blocks=[];
		
		$this->arena=Core::getInstance()->getArenaHandler()->getDuelArena($arena);
		foreach($this->getPlayersOnline() as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			Core::getInstance()->getScoreboardHandler()->sendPartyDuelScoreboard($player, $queue, $this->alive, count($this->players));
		}
	}
	
	public function getQueue():string{ return $this->queue; }

	public function getAllowSpecs():bool{ return $this->allowspecs; }
	
	public function isCombo():bool{ return $this->queue=="Combo"; }
	
	public function isSumo():bool{ return $this->queue=="Sumo"; }
	
	public function getArena(){ return $this->arena; }
	
	public function isPlayer($player):bool{
		$p=Utils::getPlayer($player);
		$name=Utils::getPlayerName($p);
		return in_array($name, $this->players);
	}
	public function getParty():?Party{
		return $this->party;
	}
	public function getPlayers():array{
		return $this->players;
	}
	public function getAlive():int{
		return $this->alive;
	}
	public function setAlive(int $int, $p){
		$this->alive=$int;
		if($p instanceof Player) $p=$p->getName();
		$this->killed[]=$p;
		foreach($this->getPlayersOnline() as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			Core::getInstance()->getScoreboardHandler()->updatePartyDuelAlive($player, $int, count($this->players));
		}
	}
	public function isAlive($player):bool{
		if($player instanceof Player) $player=$player->getName();
		return in_array($player, $this->killed)===false;
	}
	public function getPlayersOnline():array{
		$online=[];
		foreach($this->players as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			if($player!==null){
				$online[]=$player->getName();
			}
		}
		return $online;
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
		if(!$this->arePlayersOnline()){
			$this->endDuelPrematurely();
			return;
		}
		$arena=$this->getArena();
		$arenaname=$this->getArenaName();
		$duellevel=$arena->getLevel();
		if($this->isLoadingDuel()){
			if($this->countdownTick===0) $this->initializePlayers(0);
			$this->countdownTick++;
			if($this->countdownTick % 20 === 0 and $this->countdownTick !== 0){
				$second=self::MAX_COUNTDOWN_SEC - Utils::ticksToSeconds($this->countdownTick);
				
				foreach($this->getPlayersOnline() as $player){
					$player=Server::getInstance()->getPlayerExact($player);
					if($player->getLevel()->getName()!=$duellevel->getName()){
						$player->teleport($arena->getCenterPos());
					}
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
			if($this->isSumo()){
				$maxDuration=Utils::minutesToTicks(20);
			}else{
				$maxDuration=Utils::minutesToTicks(40);
			}
			if($this->fightingTick > 0){
				$this->fightingTick--;
				if(0 >= $this->fightingTick){
					$this->fightingTick=0;
				}
			}
			if($duration % 20 === 0){
				$this->updateScoreboards();
			}
			foreach($this->getPlayersOnline() as $player){
				$player=Server::getInstance()->getPlayerExact($player);
				if($this->isPlayerBelowCenter($player, 10.0) and $this->isDuelRunning()){
					/*if($this->getAlive() >= 3){
						$this->initializeLoss($player);
					}elseif($this->getAlive()===2){
						$this->initializeLoss($player);
						$this->setDuelEnded();
						return;
					}*/
					if($this->getAlive() >= 2){
						$this->initializeLoss($player);
					}
				}
			}
			if(1 >= $this->getAlive()){
				$this->setDuelEnded();
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
			$this->endDuel(false, false, true);
		}
		$this->currentTick++;
	}
	private function endDuel():void{
		$this->clearBlocks();
		$this->clearSpectators();
		$queue=$this->getQueue();
		foreach($this->getPlayersOnline() as $player){
			if($this->getParty()->isMember($player) or $this->getParty()->isLeader($player)){
				$player=Server::getInstance()->getPlayerExact($player);
				$player->sendTo(0, true);
				$player->setTagged(false);
			}
		}
		Utils::clearEntities($this->arena->getLevel(), true, true);
		Core::getInstance()->getDuelHandler()->endPartyDuel($this);
		Core::getInstance()->getArenaHandler()->setArenaOpen($this->arenaName);
		$this->getParty()->setStatus(Party::IDLE);
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
		$this->endDuel();
	}
	private function setDuelEnded(bool $result=true){
		$this->ended=$result;
		$this->endTick=$this->endTick == -1 ? $this->currentTick : $this->endTick;
	}
	private function start(){
		$this->started=true;
		foreach($this->getPlayersOnline() as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			$player->setImmobile(false);
		}
	}
	private function updateScoreboards():void{
		$duration=$this->getDurationString();
		foreach($this->getPlayersOnline() as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			Core::getInstance()->getScoreboardHandler()->updateDuelDuration($player, $duration);
		}
	}
	public function broadcastMessage(string $message):void{
		foreach($this->getPlayersOnline() as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			$player->sendMessage($message);
		}
	}
	public function broadcastTitle(string $title, string $subtitle="", int $in=0, int $stay=40, int $out=0):void{
		foreach($this->getPlayersOnline() as $player){
			$player=Server::getInstance()->getPlayerExact($player);
			$player->addTitle($title, $subtitle, $in, $stay, $out);
		}
	}
	public function broadcastSound(int $type):void{
		switch($type){
			case 0:
			foreach($this->getPlayersOnline() as $player){
				$player=Server::getInstance()->getPlayerExact($player);
				Utils::clickSound($player);
			}
			break;
			case 1:
			foreach($this->getPlayersOnline() as $player){
				$player=Server::getInstance()->getPlayerExact($player);
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
			foreach($this->getPlayersOnline() as $player){
				$player=Server::getInstance()->getPlayerExact($player);
				$player->setImmobile(false);
				$player->setGamemode(2);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
				$player->setFood(20);
				$player->setHealth(20);
			}
			break;
			case 1:
			foreach($this->getPlayersOnline() as $player){
				$player=Server::getInstance()->getPlayerExact($player);
				$queue=$this->getQueue();
				Kits::sendMatchKit($player, $queue);
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
			}
		}
	}
	public function initializeLoss($player):void{
		if(Utils::isPlayer($player)){
			if($this->isAlive($player)){
				$player->setImmobile(false);
				$player->setGamemode(3);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
				Utils::spawnLightning($player);
				$this->setAlive($this->getAlive() - 1, $player->getName());
				$this->getParty()->sendMessage(Utils::getPlayerDisplayName($player)." was killed.");
			}
		}
	}
	public function clearSpectators(){
		if(empty($this->spectators)) return;
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $spectators){
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
	public function arePlayersOnline():bool{
		return count($this->getPlayersOnline()) > 0;
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
	public function canBuild():bool{
		return $this->getArena()->canBuild();
	}
	public function canBreak():bool{
		return $this->getArena()->canBuild();
	}
	public function isBlockTooHigh(int $ycoord):bool{
		$y=$ycoord;
		$arena=$this->getArena();
		$centerY=$arena->getCenterPos()->y;
		return $y >= $centerY + 8;
    }
	//this is a height limit
	public function canPlaceBlock(Block $against):bool{
		$count=$this->countPlaced($against);
		return $count < 50;
	}
	private function countPlaced(Block $against):int{
		$count=0;
		$blAgainst=$against->asVector3();
		if($this->isPlacedBlock($against)){
			$level=$this->arena->getLevel();
			$testPos=$blAgainst->subtract(0, 1, 0);
			$belowBlock=$level->getBlock($testPos);
			$count=$this->countPlaced($belowBlock) + 1;
		}
		return $count;
	}
	public function isPlacedBlock($block){
		return $this->indexOfBlock($block) !== -1;
	}
	public function isBed($block){
		return $block instanceof Bed;
	}
	private function indexOfBlock($block):int{
		$index=-1;
		if($block instanceof Block or $block instanceof Liquid){
			$vec=$block->asVector3();
			$index=array_search($vec, $this->blocks);
			if(is_bool($index) and $index===false){
				$index=-1;
			}
		}
		return $index;
	}
	private function clearBlocks():void{
		$level=$this->getArena()->getLevel();
		$size=count($this->blocks);
		if(!empty($this->blocks)){
			foreach($this->blocks as $pos){
				$level->setBlock($pos, BlockFactory::get(Block::AIR));
			}
		}
		$this->blocks=[];
	}
	private function replaceBeds():void{
		$level=$this->getArena()->getLevel();
		$size=count($this->beds);
		for($i=0; $i < $size; $i++){
			$block=$this->beds[$i];//position
			if($block instanceof Position){
				//$level->setBlockIdAt($block->x, $block->y, $block->z, Block::BED_BLOCK);
				Tile::createTile(Tile::BED, $level, TileBed::createNBT($block)); 
			}
		}
		$this->beds=[];
	}
	public function addBlock($x, $y, $z):void{
		$pos=new Vector3($x, $y, $z);
		$this->blocks[]=$pos;
	}
	public function addBed(Block $position):void{
		$pos=$position->asVector3();
		$this->beds[]=$pos;
	}
	public function removeBlock($x, $y, $z):bool{
		$result=false;
		$level=$this->getArena()->getLevel();
		$pos=new Vector3($x, $y, $z);
		$block=$level->getBlock($pos);
		if($this->isPlacedBlock($block) or $this->isBed($block)){
			$result=true;
			unset($this->blocks[array_search($pos, $this->blocks)]);
		}
		return $result;
	}
	public function isSpectator($player):bool{
		$name=Utils::getPlayerName($player);
		return($name !== null) and isset($this->spectators[$name]);
	}
	public function addSpectator($spectator):void{
		$p=Utils::getPlayer($spectator);
		if(Core::getInstance()->getDuelHandler()->isInDuel($p)) return;
		$spectator=new DuelSpectator($p);
		$center=$this->getArena()->getSpawnPosition();
		$spectator->teleport($center);
		$name=Utils::getPlayerName($p);
		
		Kits::sendKit($p, "spectator");
		$p->sendMessage("§aYou are now spectating.");
		$this->broadcastMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $spectators){
			if($this->isSpectator($spectators)){
				if($spectators!==null){
					$spectators->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
				}
			}
		}
		$this->spectators[Utils::getPlayerName($p)]=[];
		Core::getInstance()->getScoreboardHandler()->sendPartyDuelSpectateScoreboard($p, $this->queue, $this->getParty()->getLeader());
	}
	public function removeSpectator($spectator, $send=false):void{
		if($this->isSpectator($spectator)){
			$p=Utils::getPlayer($spectator);
			unset($this->spectators[Utils::getPlayerName($p)]);
			
			if($send===true){
				$p->sendTo(0, true);
			}
			
			$p->sendMessage("§aYou are no longer spectating.");
			$this->broadcastMessage("§b".Utils::getPlayerDisplayName($p)." §fis no longer spectating.");
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $spectators){
				if($this->isSpectator($spectators)){
					if($spectators!==null){
						$spectators->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis no longer spectating.");
					}
				}
			}
		}
	}
}