<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\Task;
use Zinkil\pc\Core;

class DuelTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onRun(int $tick):void{
		$queuedPlayers=$this->plugin->getDuelHandler()->getQueuedPlayers();
		$awaitingMatches=$this->plugin->getDuelHandler()->getAwaitingGroups();
		$keys=array_keys($queuedPlayers);
		foreach($keys as $key){
			if(isset($queuedPlayers[$key])){
				$queue=$queuedPlayers[$key];
				$name=$queue->getPlayerName();
				if(!$queue==null and $this->plugin->getDuelHandler()->didFindMatch($name)){
					if($this->plugin->getDuelHandler()->isAnArenaOpen($queue->getQueue())){
						$opponent=$this->plugin->getDuelHandler()->getMatchedPlayer($name);
						$this->plugin->getDuelHandler()->setPlayersMatched($name, $opponent);
					}
				}
			}
		}
		$awaitingMatches=$this->plugin->getDuelHandler()->getAwaitingGroups();
		foreach($awaitingMatches as $match){
			$queue=$match->getQueue();
			if($this->plugin->getDuelHandler()->isAnArenaOpen($queue)){
				$this->plugin->getDuelHandler()->startDuel($match);
			}
		}
		$duels=$this->plugin->getDuelHandler()->getDuelsInProgress();
		$partyduels=$this->plugin->getDuelHandler()->getPartyDuelsInProgress();
		$botduels=$this->plugin->getDuelHandler()->getBotDuelsInProgress();
		foreach($duels as $duel){
			$duel->update();
		}
		foreach($partyduels as $partyduel){
			$partyduel->update();
		}
		foreach($botduels as $botduel){
			$botduel->update();
		}
	}
}