<?php

declare(strict_types=1);

namespace Zinkil\pc\handlers;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Human;
use pocketmine\level\Level;
use pocketmine\level\Location;
use Zinkil\pc\arenas\DuelArena;
use Zinkil\pc\duels\groups\DuelGroup;
use Zinkil\pc\duels\groups\PartyDuelGroup;
use Zinkil\pc\duels\groups\BotDuelGroup;
use Zinkil\pc\duels\groups\MatchedGroup;
use Zinkil\pc\duels\groups\MatchedBotGroup;
use Zinkil\pc\duels\groups\QueuedPlayer;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\Kits;
use Zinkil\pc\party\Party;

class DuelHandler{
	
	private $plugin;
	private $queuedPlayers;
	private $matchedGroups;
	private $matchedPlayers;
	private $duels;
	private $partyduels;
	private $botduels;
	
	public function __construct(){
		$this->plugin=Core::getInstance();
		$this->queuedPlayers=[];
		$this->matchedGroups=[];
		$this->matchedPlayers=[];
		$this->duels=[];
		$this->partyduels=[];
		$this->botduels=[];
	}
	public function startPartyDuel(Party $party, array $players, string $queue, bool $allowspecs):void{
		$arena=$this->findRandomArena($queue);
		if($this->plugin->getDuelHandler()->isAnArenaOpen($queue)){
			if($arena!==null){
				$name=$arena->getLevel()->getName();
				$level=Server::getInstance()->getLevelByName($name);
				//if(!Server::getInstance()->isLevelLoaded($name)) return;
				$world=Server::getInstance()->getLevelByName($name);
				if($world instanceof Level) $world->setAutoSave(false);
				if(count($players) >= 2){
					$duel=new PartyDuelGroup($party, $players, $queue, $allowspecs, $arena->getName());
					$this->plugin->getArenaHandler()->setArenaClosed($arena->getName());
					$this->partyduels[]=$duel;
					$party->sendMessage("The party leader has started a duel.");
					$party->setStatus(Party::DUEL);
					foreach($players as $p){
						$player=Server::getInstance()->getPlayerExact($p);
						$player->setNameTag("§c".$player->getDisplayName());
						$player->teleport($arena->getCenterPos());
					}
				}
			}
		}
	}
	public function createBotDuel($player, string $type){
		$arena=$this->findRandomArena(strtolower($type));
		foreach($arena->getLevel()->getEntities() as $entity){
			if($entity instanceof Human and !$entity instanceof Player){
				$this->plugin->getLogger()->notice("Bot entity cleared.");
				$entity->close();
			}
		}
		if(!$this->isAnArenaOpen(strtolower($type))){
			$player->sendMessage("§cThere are no open arenas, please wait.");
			return;
		}else{
			$name=$arena->getLevel()->getName();
			$level=Server::getInstance()->getLevelByName($name);
			if(!Server::getInstance()->isLevelLoaded($name)) return;
			$world=Server::getInstance()->getLevelByName($name);
			if($world instanceof Level) $world->setAutoSave(false);
			$x=$arena->getOpponentPos()->x;
			$y=$arena->getOpponentPos()->y;
			$z=$arena->getOpponentPos()->z;
			$bot=Utils::createBot($player, strtolower($type), $x, $y, $z, $level);
			if($player!==null and $bot!==null){
				$group=new MatchedBotGroup(Utils::getPlayerName($player), $bot, $type);
				$duel=new BotDuelGroup($group, $arena->getName());
				$this->plugin->getArenaHandler()->setArenaClosed($arena->getName());
				$this->botduels[]=$duel;
				
				$p=$group->getPlayer();
				$p->setNameTag("§c".$p->getDisplayName());
				$p->setImmobile(true);
				$p->getInventory()->clearAll();
				$p->getArmorInventory()->clearAll();
				$p->removeAllEffects();
				$p->setFood(20);
				$p->setHealth(20);
				$this->plugin->getScoreboardHandler()->sendBotDuelScoreboard($p, "§f".$bot->getName());
				
				$bot->setTarget($p);
				$bot->setDuel($duel);
				$bot->setImmobile(true);
				$bot->getInventory()->clearAll();
				$bot->getArmorInventory()->clearAll();
				$bot->removeAllEffects();
				$bot->setFood(20);
				$bot->setHealth(20);
				
				$duelarena=$duel->getArena();
				$duellevel=$arena->getLevel();
				$isPlayer=$duel->isPlayer($p->getName());
				
				$playerpos=$arena->getPlayerPos();
				$opponentpos=$arena->getOpponentPos();
				
				$p->teleport($playerpos);
				$bot->teleport($opponentpos);
				
			}
		}
	}
	public function addPlayerToQueue($player, string $queue, bool $isRanked=false){
		$name=Utils::getPlayerName($player);
		$pe=true;
		$newQueue=new QueuedPlayer($name, $queue, $isRanked, $pe);
		if(!$this->isPlayerInQueue($name)){
			$this->queuedPlayers[$name]=$newQueue;
			if($isRanked===true){
				$player->sendMessage("§aYou are now queued for Ranked ".$queue.".");
			}
			if($isRanked===false){
				$player->sendMessage("§aYou are now queued for Unranked ".$queue.".");
			}
		}
	}
	public function removePlayerFromQueue($player):void{
		if($this->isPlayerInQueue($player)){
			$queue=$this->getQueuedPlayer($player);
			if($queue instanceof QueuedPlayer){
				unset($this->queuedPlayers[$queue->getPlayerName()]);
				$player->sendMessage("§aYou left the queue.");
			}
		}
	}
	public function setPlayersMatched($player, $opponent, bool $isDirect=false, string $queue=null):void{
		if(!$isDirect){
			if($this->isPlayerInQueue($player) and $this->isPlayerInQueue($opponent)){
				$playerqueue=$this->getQueuedPlayer($player);
				$opponentqueue=$this->getQueuedPlayer($opponent);
				$playerName=$playerqueue->getPlayerName();
				$opponentName=$opponentqueue->getPlayerName();
				
				$onlineP=Utils::getPlayer($playerName);
				$onlineO=Utils::getPlayer($opponentName);
				
				$playerDisplayName=Utils::getPlayerDisplayName($onlineP);
				$opponentDisplayName=Utils::getPlayerDisplayName($onlineO);
				
				$ranked=$playerqueue->isRanked();
				$queue=$playerqueue->getQueue();
				if($this->isAnArenaOpen($queue)){
					$p=$playerqueue->getPlayer();
					$o=$opponentqueue->getPlayer();
					$opponentRankedElo=$this->plugin->getDatabaseHandler()->getRankedElo($opponentName);
					$playerRankedElo=$this->plugin->getDatabaseHandler()->getRankedElo($playerName);
					$group=new MatchedGroup($playerName, $opponentName, $queue, $ranked);
					$this->matchedGroups[]=$group;
					$this->matchedPlayers[]=$playerqueue;
					$this->matchedPlayers[]=$opponentqueue;
					
					$p->sendMessage("§fMatch Found!");
					$p->sendMessage("§cOpponent: ".$opponentDisplayName." (".$opponentRankedElo." Elo)");
					
					$o->sendMessage("§fMatch Found!");
					$o->sendMessage("§cOpponent: ".$playerDisplayName." (".$playerRankedElo." Elo)");
					
					if($ranked===true){
						$this->plugin->getScoreboardHandler()->sendDuelScoreboard($p, "Ranked", $queue, $opponentDisplayName);
						$this->plugin->getScoreboardHandler()->sendDuelScoreboard($o, "Ranked", $queue, $playerDisplayName);
					}else{
						$this->plugin->getScoreboardHandler()->sendDuelScoreboard($p, "Unranked", $queue, $opponentDisplayName);
						$this->plugin->getScoreboardHandler()->sendDuelScoreboard($o, "Unranked", $queue, $playerDisplayName);
					}
					
					unset($this->queuedPlayers[$playerName], $this->queuedPlayers[$opponentName]);
				}
			}
		}else{
			if(!is_null($queue)){
				if($this->isPlayerInQueue($player)) $this->removePlayerFromQueue($player);
				if($this->isPlayerInQueue($opponent)) $this->removePlayerFromQueue($opponent);
				$group=new MatchedGroup($player, $opponent, $queue, false);
				$this->matchedGroups[]=$group;
			}
		}
	}
	public function getOpenArenas(string $queue):array{
		$result=[];
		$arenas=$this->plugin->getArenaHandler()->getDuelArenas();
		foreach($arenas as $arena){
			$closed=$this->plugin->getArenaHandler()->isArenaClosed($arena->getName());
			if($closed===false){
				$modes=$arena->getModes($queue);
				$nocapsqueue=strtolower($queue);
				if(in_array($nocapsqueue, $modes)===true) $result[]=$arena;
			}
		}
		return $result;
	}
	public function isAnArenaOpen(string $queue):bool{
		return count($this->getOpenArenas(strtolower($queue))) > 0;
	}
	private function findRandomArena(string $queue){
		$result=null;
		if($this->isAnArenaOpen($queue)){
			$openArenas=$this->getOpenArenas($queue);
			$count=count($openArenas);
			$rand=rand(0, $count - 1);
			$res=$openArenas[$rand];
			$result=$res;
		}
		return $result;
	}
	public function isPlayerInQueue($player):bool{
		$name=Utils::getPlayerName($player);
		return($name !== null) and isset($this->queuedPlayers[$name]);
	}
	public function getAwaitingGroups():array{
		return $this->matchedGroups;
	}
	public function getDuelsInProgress():array{
		return $this->duels;
	}
	public function getNumberOfDuelsInProgress():int{
		return count($this->duels);
	}
	public function getPartyDuelsInProgress():array{
		return $this->partyduels;
	}
	public function getNumberOfPartyDuelsInProgress():int{
		return count($this->partyduels);
	}
	public function getBotDuelsInProgress():array{
		return $this->botduels;
	}
	public function getNumberOfBotDuelsInProgress():int{
		return count($this->botduels);
	}
	public function didFindMatch($player):bool{
		return !is_null($this->findQueueMatch($player));
	}
	public function getQueuedPlayers():array{
		return $this->queuedPlayers;
	}
	public function getNumberOfQueuedPlayers():int{
		return count($this->queuedPlayers);
	}
	public function getNumberOfMatchedGroups():int{
		return count($this->matchedGroups);
	}
	public function getNumberQueuedFor(string $queue, bool $ranked):int{
		$result=0;
		foreach($this->queuedPlayers as $aQueue){
			if($aQueue->getQueue()===$queue and $ranked===$aQueue->isRanked()){
				$result++;
			}
		}
		return $result;
	}
	public function getQueuedPlayer($player){
		$name=Utils::getPlayerName($player);
		$result=null;
		if($this->isPlayerInQueue($player)){
			$result=$this->queuedPlayers[$name];
			return $result;
		}
	}
	private function findQueueMatch($player){
		$opponent=null;
		if(isset($player) and $this->isPlayerInQueue($player)){
			$playerqueue=$this->getQueuedPlayer($player);
			//$peQueueCheck=$playerqueue->isPEOnly();
			foreach($this->queuedPlayers as $queue){
				$equals=$queue->equals($playerqueue);
				if($equals!==true){
					if($playerqueue->hasSameQueue($queue)){
						$found=true;/*
						if($peQueueCheck===true){
							if($queue->getPlayer()->peOnlyQueue()){
								$found=true;
								}else{
									if($queue->isPEOnly()){
										$found=$playerqueue->getPlayer()->peOnlyQueue();
										}else{
											$found=true;
											}
										}
									}*/
									if($found===true){
										$opponent=$queue;
										break;
							}
						}
					}
				}
			}
			return $opponent;
	}
	public function isPlayerMatched($player):bool{
		$name=Utils::getPlayerName($player);
		return($name !== null) and isset($this->matchedPlayers[$name]);
	}
	public function getMatchedPlayer($player){
		$opponent=null;
		if($this->didFindMatch($player)){
			$otherQueue=$this->findQueueMatch($player);
			if($otherQueue!==null){
				$opponent=Server::getInstance()->getPlayer($otherQueue->getPlayerName());
			}
			return $opponent;
		}
	}
	public function getNumberMatchedFor(string $queue, bool $ranked):int{
		$result=0;
		foreach($this->matchedGroups as $aMatch){
			if($aMatch->getQueue()===$queue and $ranked===$aMatch->isRanked()){
				$result++;
			}
		}
		return $result;
	}
	public function getNumberOfDuelsOfQueue(string $queue, bool $ranked):int{
		$result=0;
		foreach($this->duels as $duel){
			if($duel->getQueue()===$queue and $ranked===$duel->isRanked()){
				$result++;
			}
		}
		return $result;
	}
	private function getMatchedIndexOf(MatchedGroup $group):int{
		$index=array_search($group, $this->matchedGroups);
		if(is_bool($index) and $index===false){
			$index=-1;
		}
		return $index;
	}
	private function getDuelIndexOf(DuelGroup $group):int{
		$index=array_search($group, $this->duels);
		if(is_bool($index) and $index===false){
			$index=-1;
		}
		return $index;
	}
	private function getPartyDuelIndexOf(PartyDuelGroup $group):int{
		$index=array_search($group, $this->partyduels);
		if(is_bool($index) and $index===false){
			$index=-1;
		}
		return $index;
	}
	private function getBotDuelIndexOf(BotDuelGroup $group):int{
		$index=array_search($group, $this->botduels);
		if(is_bool($index) and $index===false){
			$index=-1;
		}
		return $index;
	}
	public function isWaitingForDuelToStart($player):bool{
		return !is_null($this->getGroupFrom($player));
	}
	public function getGroupFrom($player){
		$str=Utils::getPlayerName($player);
		$result=null;
		if(!is_null($str)){
			foreach($this->matchedGroups as $group){
				if($group->getPlayerName()===$str or $group->getOpponentName()===$str){
					$result=$group;
					break;
				}
			}
		}
		return $result;
	}
	private function isValidMatched(MatchedGroup $group):bool{
		return $this->getMatchedIndexOf($group) !== -1;
	}
	
	public function startDuel(MatchedGroup $group):void{
		$arena=$this->findRandomArena($group->getQueue());
		foreach($arena->getLevel()->getEntities() as $entity){
			if($entity instanceof Human and !$entity instanceof Player){
				$this->plugin->getLogger()->notice("Bot entity cleared.");
				$entity->close();
			}
		}
		$name=$arena->getLevel()->getName();
		$level=Server::getInstance()->getLevelByName($name);
		if(!Server::getInstance()->isLevelLoaded($name)) return;
		$world=Server::getInstance()->getLevelByName($name);
		if($world instanceof Level){
			//$this->plugin->getLogger()->notice("is instanceof level");
			$world->setAutoSave(false);
		}
		if(!is_null($arena) and $this->isValidMatched($group)){
			$index=$this->getMatchedIndexOf($group);
			if($group->isPlayerOnline() and $group->isOpponentOnline()){
				$duel=new DuelGroup($group, $arena->getName());
				$this->plugin->getArenaHandler()->setArenaClosed($arena->getName());
				$this->duels[]=$duel;
				
				$p=$group->getPlayer();
				$o=$group->getOpponent();
				
				$duelp=$this->plugin->getDuelHandler()->getDuelFromSpec($p);
				if(!is_null($duelp)) $duelp->removeSpectator($p);
				
				$duelo=$this->plugin->getDuelHandler()->getDuelFromSpec($o);
				if(!is_null($duelo)) $duelo->removeSpectator($o);
				
				$p->setNameTag("§c".$p->getDisplayName());
				$o->setNameTag("§c".$o->getDisplayName());
				
				$duelarena=$duel->getArena();
				$duellevel=$arena->getLevel();
				$isPlayer=$duel->isPlayer($p->getName());
				$isOpponent=$duel->isOpponent($o->getName());
				
				$playerpos=($isPlayer===true) ? $arena->getPlayerPos() : $arena->getOpponentPos();
				$opponentpos=($isOpponent===true) ? $arena->getOpponentPos() : $arena->getPlayerPos();
				
				$p->teleport($playerpos);
				$o->teleport($opponentpos);
			}
			unset($this->matchedGroups[$index]);
			$this->matchedGroups=array_values($this->matchedGroups);
		}
	}
	public function endDuel(DuelGroup $group){
		if($this->isValidDuel($group)){
			$index=$this->getDuelIndexOf($group);
			$arena=$group->getArena();
			unset($this->duels[$index]);
		} else unset($group);
		$this->duels=array_values($this->duels);
	}
	public function endPartyDuel(PartyDuelGroup $group){
		if($this->isValidPartyDuel($group)){
			$index=$this->getPartyDuelIndexOf($group);
			$arena=$group->getArena();
			unset($this->partyduels[$index]);
		} else unset($group);
		$this->partyduels=array_values($this->partyduels);
	}
	public function endBotDuel(BotDuelGroup $group){
		if($this->isValidBotDuel($group)){
			$index=$this->getBotDuelIndexOf($group);
			$arena=$group->getArena();
			unset($this->botduels[$index]);
		} else unset($group);
		$this->botduels=array_values($this->botduels);
	}
	public function getDuel($object, bool $isArena=false){
		$result=null;
		if(isset($object) and !is_null($object)){
			if($isArena===false){
				$player=Utils::getPlayerName($object);
				foreach($this->duels as $duel){
					if($duel->isPlayer($player) or $duel->isOpponent($player)){
						$result=$duel;
						break;
					}
				}
			}else{
				if(is_string($object) and $this->plugin->getArenaHandler()->isDuelArena($object)) {
					$arena=$this->plugin->getArenaHandler()->getDuelArena($object);
					$name=$arena->getName();
					foreach($this->duels as $duel){
						$arenaName=$duel->getArenaName();
						if($arenaName===$name){
							$result=$duel;
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	public function getPartyDuel($object, bool $isArena=false){
		$result=null;
		if(isset($object) and !is_null($object)){
			if($isArena===false){
				$player=Utils::getPlayerName($object);
				foreach($this->partyduels as $duel){
					if($duel->isPlayer($player)){
						$result=$duel;
						break;
					}
				}
			}else{
				if(is_string($object) and $this->plugin->getArenaHandler()->isDuelArena($object)) {
					$arena=$this->plugin->getArenaHandler()->getDuelArena($object);
					$name=$arena->getName();
					foreach($this->partyduels as $duel){
						$arenaName=$duel->getArenaName();
						if($arenaName===$name){
							$result=$duel;
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	public function getBotDuel($object, bool $isArena=false){
		$result=null;
		if(isset($object) and !is_null($object)){
			if($isArena===false){
				$player=Utils::getPlayerName($object);
				foreach($this->botduels as $duel){
					if($duel->isPlayer($player)){
						$result=$duel;
						break;
					}
				}
			}else{
				if(is_string($object) and $this->plugin->getArenaHandler()->isDuelArena($object)) {
					$arena=$this->plugin->getArenaHandler()->getDuelArena($object);
					$name=$arena->getName();
					foreach($this->botduels as $duel){
						$arenaName=$duel->getArenaName();
						if($arenaName===$name){
							$result=$duel;
							break;
						}
					}
				}
			}
		}
		return $result;
	}
	public function isInDuel($player):bool{
		return !is_null($this->getDuel($player));
	}
	public function isInPartyDuel($player):bool{
		$duel=$this->getPartyDuel($player);
		return !is_null($this->getPartyDuel($player)) and $duel->isAlive($player);
	}
	public function isInBotDuel($player):bool{
		return !is_null($this->getBotDuel($player));
	}
	public function isArenaInUse($arena):bool{
		return !is_null($this->getDuel($arena, true)) and !is_null($this->getBotDuel($arena, true));
	}
	private function isValidDuel(DuelGroup $group):bool{
		return $this->getDuelIndexOf($group) !== -1;
	}
	private function isValidPartyDuel(PartyDuelGroup $group):bool{
		return $this->getPartyDuelIndexOf($group) !== -1;
	}
	private function isValidBotDuel(BotDuelGroup $group):bool{
		return $this->getBotDuelIndexOf($group) !== -1;
	}
	public function getDuelFromSpec($spec){
		$result=null;
		$player=Utils::getPlayer($spec);
		foreach($this->duels as $duel){
			if($duel->isSpectator(Utils::getPlayerName($player))){
				$result=$duel;
			}
		}
		return $result;
	}
	public function getPartyDuelFromSpec($spec){
		$result=null;
		$player=Utils::getPlayer($spec);
		foreach($this->partyduels as $duel){
			if($duel->isSpectator(Utils::getPlayerName($player))){
				$result=$duel;
			}
		}
		return $result;
	}
	public function isASpectator($player):bool{
		$duel=$this->getDuelFromSpec($player);
		$pduel=$this->getPartyDuelFromSpec($player);
		return !is_null($duel) and !is_null($pduel);
	}
}