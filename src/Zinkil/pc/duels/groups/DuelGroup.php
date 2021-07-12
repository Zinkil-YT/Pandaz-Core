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

class DuelGroup{
	
	public const NONE="None";
	
	public const MAX_COUNTDOWN_SEC=7;
	public const MAX_DURATION_MIN=1;
	public const MAX_END_DELAY_SEC=5;
	
	private $playerName;
	private $opponentName;
	private $arenaName;
	private $winnerName;
	private $loserName;
	
	private $queue;
	private $started;
	private $ended;
	private $spectators;
	private $playerSwings;
	private $oppSwings;
	private $playerHits;
	private $oppHits;
	private $playerPots;
	private $oppPots;
	private $arena;
	
	private $origOppTag;
	private $origPlayerTag;
	
	private $currentTick;
	private $countdownTick;
	private $endTick;

	public function __construct(MatchedGroup $group, string $arena){
		$this->playerName=$group->getPlayerName();
		$this->opponentName=$group->getOpponentName();
		
		$this->winnerName=self::NONE;
		$this->loserName=self::NONE;
		$this->arenaName=$arena;
		
		$this->queue=$group->getQueue();
		$this->ranked=$group->isRanked();
		
		$player=$group->getPlayer();
		$opponent=$group->getOpponent();
		$p=$player->getPlayer();
		$o=$opponent->getPlayer();
		
		$this->origPlayerTag=$p->getNameTag();
		$this->origOppTag=$o->getNameTag();
		
		$this->maxCountdownTicks=Utils::secondsToTicks(self::MAX_COUNTDOWN_SEC);
		
		$this->fightingTick=0;
		$this->currentTick=0;
		$this->countdownTick=0;
		$this->endTick=-1;
		
		$this->started=false;
		$this->ended=false;
		
		$this->playerHits=[];
		$this->oppHits=[];
		$this->accPlayerSwings=0;
		$this->accOppSwings=0;
		$this->accPlayerHits=0;
		$this->accOppHits=0;
		$this->accPlayerMisses=0;
		$this->accOppMisses=0;
		$this->playerPots=0;
		$this->oppPots=0;
		$this->spectators=[];
		$this->blocks=[];
		$this->beds=[];
		
		$this->arena=Core::getInstance()->getArenaHandler()->getDuelArena($arena);
	}
	public function isRanked():bool{ return $this->ranked; }
	
	public function getQueue():string{ return $this->queue; }
	
	public function isCombo():bool{ return $this->queue=="Combo"; }
	
	public function isSumo():bool{ return $this->queue=="Sumo"; }
	
	public function isLine():bool{ return $this->queue=="Line"; }
	
	public function isBedwars():bool{ return $this->queue=="Bedwars"; }
	
	public function getArena(){ return $this->arena; }
	
	public function isPlayer($player):bool{
		$result=false;
		$p=Utils::getPlayer($player);
		$name=Utils::getPlayerName($p);
		$result=$name===$this->playerName;
		return $result;
	}
	public function isOpponent($player):bool{
		$result=false;
		$p=Utils::getPlayer($player);
		$name=Utils::getPlayerName($p);
		$result=$name===$this->opponentName;
		return $result;
	}
	public function getPlayer(){
		return Utils::getPlayer($this->playerName);
	}
	public function getOpponent(){
		return Utils::getPlayer($this->opponentName);
	}
	public function getPlayerName():string{
		return $this->playerName;
	}
	public function getOpponentName():string{
		return $this->opponentName;
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
			if($this->isDuelRunning()) $this->setResults($this->opponentName, $this->playerName);
			$this->endDuel();
			return;
		}elseif(!$this->isOpponentOnline()){
			if($this->isDuelRunning()) $this->setResults($this->playerName, $this->opponentName);
			$this->endDuel();
			return;
		}elseif(!$this->arePlayersOnline()){
			$this->endDuelPrematurely();
			return;
		}
		$arena=$this->getArena();
		$arenaname=$this->getArenaName();
		$duellevel=$arena->getLevel();
		$player=$this->getPlayer();
		$opponent=$this->getOpponent();
		$queue=$this->getQueue();
		$bar = new BossBar();
		if($this->isLoadingDuel()){
			if($this->countdownTick===0) $this->initializePlayers(0);
			$this->countdownTick++;
			if($this->countdownTick % 20 === 0 and $this->countdownTick !== 0){
				$second=self::MAX_COUNTDOWN_SEC - Utils::ticksToSeconds($this->countdownTick);
				
				if($player->getLevel()->getName()!=$duellevel->getName()){
					$player->teleport($arena->getPlayerPos());
				}
				if($opponent->getLevel()->getName()!=$duellevel->getName()){
					$opponent->teleport($arena->getOpponentPos());
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
			if($this->isSumo() or $this->isLine()){
				$maxDuration=Utils::minutesToTicks(5);
			}else{
				$maxDuration=Utils::minutesToTicks(30);
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
			if($this->isPlayerBelowCenter($player, 10.0) and $this->isDuelRunning()){
				$this->setResults($this->opponentName, $this->playerName);
				return;
			}
			if($this->isPlayerBelowCenter($opponent, 10.0) and $this->isDuelRunning()){
				$this->setResults($this->playerName, $this->opponentName);
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
	public function setResults($winner=self::NONE, $loser=self::NONE){
		//if($this->didDuelEnd()) return;
		$this->winnerName=$winner;
		$this->loserName=$loser;
		if($winner!==self::NONE and $loser!==self::NONE){
			$player=Utils::getPlayer($winner);
			$opponent=Utils::getPlayer($loser);
			if(!is_null($opponent)) Utils::spawnLightning($opponent);
			if($this->isRanked()===true){
				Core::getInstance()->getServer()->broadcastMessage("§a".Utils::getPlayerDisplayName($this->winnerName)."§e won a Ranked ".$this->getQueue()." match against §c".Utils::getPlayerDisplayName($this->loserName)."!");
			}else{
				Core::getInstance()->getServer()->broadcastMessage("§a".Utils::getPlayerDisplayName($this->winnerName)."§e won an Unranked ".$this->getQueue()." match against §c".Utils::getPlayerDisplayName($this->loserName)."!");
			}
			$this->initializeWin($player);
			$this->initializeLoss($opponent);
		}
		$this->setDuelEnded();
	}
	private function endDuel(bool $endPrematurely=false, bool $disablePlugin=false, $win=false):void{
		if($this->isBedwars()) $this->replaceBeds();
		$this->clearBlocks();
		$this->clearSpectators();
		$queue=$this->getQueue();
		$ranked=$this->isRanked();
		$player=Utils::getPlayer($this->playerName);
		$opponent=Utils::getPlayer($this->opponentName);
		$winner=Utils::getPlayer($this->winnerName);
		$loser=Utils::getPlayer($this->loserName);
		if($this->isPlayerOnline()){
			$finalhealthP=round($player->getHealth(), 1);
		}else{
			$finalhealthP=0;
		}
		if($this->isOpponentOnline()){
			$finalhealthO=round($opponent->getHealth(), 1);
		}else{
			$finalhealthO=0;
		}
		$ppots=0;
		$opots=0;
		if($this->isPlayerOnline()){
			foreach($player->getInventory()->getContents() as $pots){
				if($pots instanceof ItemSplashPotion) $ppots++;
			}
		}
		if($this->isOpponentOnline()){
			foreach($opponent->getInventory()->getContents() as $pots){
				if($pots instanceof ItemSplashPotion) $opots++;
			}
		}
		if($player instanceof CPlayer) $player->sendTo(0, true);
		if($player instanceof CPlayer) $player->setTagged(false);
		if($opponent instanceof CPlayer) $opponent->sendTo(0, true);
		if($opponent instanceof CPlayer) $opponent->setTagged(false);
		if($win===true){
			if($winner!==self::NONE and $loser!==self::NONE){
				if(!Core::getInstance()->getDuelHandler()->isPlayerInQueue($winner)){
					if(Utils::isAutoRequeueEnabled($winner)==true){
						Core::getInstance()->getDuelHandler()->addPlayerToQueue($winner, $queue, $ranked);
						if($ranked===true){
							$winner->sendMessage("§aYou were automatically re-queued for Ranked ".$queue.".");
						}else{
							$winner->sendMessage("§aYou were automatically re-queued for Unranked ".$queue.".");
						}
					}
				}
			}
		}
		Utils::clearEntities($this->arena->getLevel(), true, true);
		Core::getInstance()->getDuelHandler()->endDuel($this);
		Core::getInstance()->getArenaHandler()->setArenaOpen($this->arenaName);
	}
	public function endDuelPrematurely(bool $disablePlugin=false):void{
		$winner=self::NONE;
		$loser=self::NONE;
		$premature=true;
		if($disablePlugin===true){
			$this->setDuelEnded();
		}
		if($disablePlugin===false){
			if($this->isDuelRunning() or $this->didDuelEnd()){
				$results=$this->getOfflinePlayers();
				$winner=$results["winner"];
				$loser=$results["loser"];
				$premature=false;
			}
		}
		$this->winnerName=$winner;
		$this->loserName=$loser;
		$this->endDuel($premature, $disablePlugin);
	}
	private function setDuelEnded(bool $result=true){
		$this->ended=$result;
		$this->endTick=$this->endTick == -1 ? $this->currentTick : $this->endTick;
	}
	private function start(){
		$this->started=true;
		if($this->arePlayersOnline()){
			$player=$this->getPlayer();
			$opponent=$this->getOpponent();
			$player->setImmobile(false);
			$opponent->setImmobile(false);
		}
	}
	private function updateScoreboards():void{
		$duration=$this->getDurationString();
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			Core::getInstance()->getScoreboardHandler()->updateDuelDuration($player, $duration);
		}
		if($this->isOpponentOnline()){
			$opponent=$this->getOpponent();
			Core::getInstance()->getScoreboardHandler()->updateDuelDuration($opponent, $duration);
		}
	}
	public function broadcastMessage(string $message):void{
		if($this->isOpponentOnline()){
			$opponent=$this->getOpponent();
			$opponent->sendMessage($message);
		}
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			$player->sendMessage($message);
		}
	}
	public function broadcastTitle(string $title, string $subtitle="", int $in=0, int $stay=40, int $out=0):void{
		if($this->isOpponentOnline()){
			$opponent=$this->getOpponent();
			$opponent->addTitle($title, $subtitle, $in, $stay, $out);
		}
		if($this->isPlayerOnline()){
			$player=$this->getPlayer();
			$player->addTitle($title, $subtitle, $in, $stay, $out);
		}
	}
	public function broadcastSound(int $type):void{
		switch($type){
			case 0:
			if($this->isOpponentOnline()){
				$opponent=$this->getOpponent();
				//Utils::playSound($opponent, 50);
				Utils::clickSound($opponent);
			}
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				Utils::clickSound($player);
			}
			break;
			case 1:
			if($this->isOpponentOnline()){
				$opponent=$this->getOpponent();
				//Utils::shootSound($opponent);
			}
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
			if($this->isOpponentOnline()){
				$opponent=$this->getOpponent();
				$opponent->setImmobile(true);
				$opponent->setGamemode(2);
				$opponent->getInventory()->clearAll();
				$opponent->getArmorInventory()->clearAll();
				$opponent->removeAllEffects();
				$opponent->setFood(20);
				$opponent->setHealth(20);
			}
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				$player->setImmobile(true);
				$player->setGamemode(2);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
				$player->setFood(20);
				$player->setHealth(20);
			}
			break;
			case 1:
			if($this->isOpponentOnline()){
				$opponent=$this->getOpponent();
				$opponent->setImmobile(false);
				$queue=$this->getQueue();
				Kits::sendMatchKit($opponent, $queue);
				
			}
			if($this->isPlayerOnline()){
				$player=$this->getPlayer();
				$player->setImmobile(false);
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
				$player->addTitle("§l§aVICTORY", "§r§eYou won", 10, 30, 40);
				$ranked=$this->isRanked();
				if($ranked===true){
					if($player instanceof CPlayer) Utils::matchOutcome($player, 0, true);
				}else{
					if($player instanceof CPlayer) Utils::matchOutcome($player, 0, false);
				}
			}
		}
	}
	public function initializeLoss($player):void{
		if(Utils::isPlayer($player)){
			if(!is_null($player)){
				$player->setImmobile(false);
				$player->addTitle("§l§cDEFEAT", "§r§eYou lost", 10, 30, 40);
				$ranked=$this->isRanked();
				$player->setGamemode(3);
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->removeAllEffects();
				if($ranked===true){
					if($player instanceof CPlayer) Utils::matchOutcome($player, 1, true);
				}else{
					if($player instanceof CPlayer) Utils::matchOutcome($player, 1, false);
				}
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
		$result=false;
		if(Utils::isPlayer($this->opponentName) and Utils::isPlayer($this->playerName)){
			$opp=$this->getOpponent();
			$pl=$this->getPlayer();
			$result=$opp->isOnline() and $pl->isOnline();
		}
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
	public function isOpponentOnline():bool{
		$result=false;
		if(Utils::isPlayer($this->opponentName)){
			$opponent=$this->getOpponent();
			$result=$opponent->isOnline();
		}
		return $result;
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
	private function getOfflinePlayers():array{
		$result=["winner" => self::NONE, "loser" => self::NONE];
		if(!$this->arePlayersOnline()){
			if(!is_null($this->getPlayer()) and $this->getPlayer()->isOnline()){
				$result["winner"]=$this->playerName;
				$result["loser"]=$this->opponentName;
				} elseif(!is_null($this->getOpponent()) and $this->getOpponent()->isOnline()){
					$result["winner"]=$this->opponentName;
					$result["loser"]=$this->playerName;
			}
		}
		return $result;
	}
	public function addHitFor($player){
		if($this->isPlayer($player)){
			$hit=new DuelHit($this->playerName, $this->currentTick);
			$add=true;
			$size=count($this->playerHits) - 1;
			for($i=$size; $i > -1; $i--){
				$pastHit=$this->playerHits[$i];
				if($pastHit->equals($hit)){
					$add=false;
					break;
				}
			}
			if($add===true) $this->playerHits[]=$hit;
		}elseif($this->isOpponent($player)){
			$hit=new DuelHit($this->opponentName, $this->currentTick);
			$add=true;
			$size=count($this->oppHits) - 1;
			for($i=$size; $i > -1; $i--){
				$pastHit = $this->oppHits[$i];
				if($pastHit->equals($hit)){
					$add=false;
					break;
				}
			}
			if($add===true) $this->oppHits[]=$hit;
		}
	}
	public function addAccHitFor($player){
		if($this->isPlayer($player)){
			$this->accPlayerHits++;
			$this->accPlayerSwings++;
		}elseif($this->isOpponent($player)){
			$this->accOppHits++;
			$this->accOppSwings++;
		}
	}
	public function addAccMissFor($player){
		if($this->isPlayer($player)){
			$this->accPlayerMisses++;
			$this->accPlayerSwings++;
		}elseif($this->isOpponent($player)){
			$this->accOppMisses++;
			$this->accOppSwings++;
		}
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
		//$this->spectators[$name]=$spectator;
		
		$player=$this->getPlayer();
		$opponent=$this->getOpponent();
		$playerDS=Utils::getPlayerDisplayName($player);
		$opponentDS=Utils::getPlayerDisplayName($opponent);
		
		Kits::sendKit($p, "spectator");
		$p->sendMessage("§aYou are now spectating.");
		if($this->isPlayerOnline()) $player->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
		if($this->isOpponentOnline()) $opponent->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $spectators){
			if($this->isSpectator($spectators)){
				if($spectators!==null){
					$spectators->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis now spectating.");
				}
			}
		}
		$this->spectators[Utils::getPlayerName($p)]=[];
		if($this->ranked===true){
			Core::getInstance()->getScoreboardHandler()->sendDuelSpectateScoreboard($p, "Ranked", $this->queue, $playerDS, $opponentDS);
		}else{
			Core::getInstance()->getScoreboardHandler()->sendDuelSpectateScoreboard($p, "Unranked", $this->queue, $playerDS, $opponentDS);
		}
	}
	public function removeSpectator($spectator, $send=false):void{
		if($this->isSpectator($spectator)){
			$p=Utils::getPlayer($spectator);
			unset($this->spectators[Utils::getPlayerName($p)]);
			
			$player=$this->getPlayer();
			$opponent=$this->getOpponent();
			$playerDS=Utils::getPlayerDisplayName($player);
			$opponentDS=Utils::getPlayerDisplayName($opponent);
			
			if($send===true){
				$p->sendTo(0, true);
			}
			
			$p->sendMessage("§aYou are no longer spectating.");
			if($this->isPlayerOnline()) $player->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis no longer spectating.");
			if($this->isOpponentOnline()) $opponent->sendMessage("§b".Utils::getPlayerDisplayName($p)." §fis no longer spectating.");
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $spectators){
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