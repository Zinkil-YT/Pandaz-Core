<?php

declare(strict_types=1);

namespace Zinkil\pc\handlers;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class ScoreboardHandler{
	
	private $plugin;
	private $scoreboard=[];
	private $main=[];
	private $duel=[];
	private $spectator=[];

	public function __construct(){
		$this->plugin=Core::getInstance();
	}
	public function sendMainScoreboard($player, string $title="Pandaz"):void{
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			$this->removeScoreboard($player);
		}
		$this->lineTitle($player, "§l§f» §l§bPandaz §l§f«");
		$this->lineCreate($player, 1, "§f----------------- ");
		$this->lineCreate($player, 2, "§fOnline: §b".count($this->plugin->getServer()->getOnlinePlayers()));
		$this->lineCreate($player, 3, "   ");
		$this->lineCreate($player, 4, "§fYour Ping: §b".$player->getPing()."ms");
		$this->lineCreate($player, 5, "     ");
		$this->lineCreate($player, 6, "§fK: §b".$this->plugin->getDatabaseHandler()->getKills($player)." §fD: §b".$this->plugin->getDatabaseHandler()->getDeaths($player));
		$this->lineCreate($player, 7, "§fKDR: §b".$this->plugin->getDatabaseHandler()->getKdr($player)." §fElo: §b".$this->plugin->getDatabaseHandler()->getRankedElo($player));
		$this->lineCreate($player, 8, "§fKillstreak: §b".$this->plugin->getDatabaseHandler()->getKillstreak($player)." §7(".$this->plugin->getDatabaseHandler()->getBestKillstreak($player).")");
		$this->lineCreate($player, 9, "§f-----------------");
		$this->scoreboard[$player->getName()]=$player->getName();
		$this->main[$player->getName()]=$player->getName();
	}
	public function sendDuelScoreboard($player, string $type, string $queue, string $opponent):void{
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			$this->removeScoreboard($player);
		}
		$this->lineTitle($player, "§l§f» §l§bPandaz §l§f«");
		$this->lineCreate($player, 1, "§f-------------------- ");
		$this->lineCreate($player, 2, "§f".$type.": §b".$queue);
		$this->lineCreate($player, 3, "    ");
		$this->lineCreate($player, 4, "§fFighting: §c".$opponent);
		$this->lineCreate($player, 5, "§fDuration: §600:00");
		$this->lineCreate($player, 6, "§f--------------------");
		$this->scoreboard[$player->getName()]=$player->getName();
		$this->duel[$player->getName()]=$player->getName();
	}
	public function sendPartyDuelScoreboard($player, string $queue, int $alive, int $playing):void{
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			$this->removeScoreboard($player);
		}
		$this->lineTitle($player, "§l§f» §l§bPandaz §l§f«");
		$this->lineCreate($player, 1, "§f-------------------- ");
		$this->lineCreate($player, 2, "§fParty: §b".$queue);
		$this->lineCreate($player, 3, "    ");
		$this->lineCreate($player, 4, "§fAlive: §c".$alive."/".$playing);
		$this->lineCreate($player, 5, "§fDuration: §600:00");
		$this->lineCreate($player, 6, "§f--------------------");
		$this->scoreboard[$player->getName()]=$player->getName();
		$this->duel[$player->getName()]=$player->getName();
	}
	public function sendBotDuelScoreboard($player, string $opponent):void{
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			$this->removeScoreboard($player);
		}
		$this->lineTitle($player, "§l§f» §l§bPandaz §l§f«");
		$this->lineCreate($player, 1, "§f-------------------- ");
		$this->lineCreate($player, 2, "§fFighting: §c".$opponent);
		$this->lineCreate($player, 3, "§fDuration: §600:00");
		$this->lineCreate($player, 4, "§f--------------------");
		$this->scoreboard[$player->getName()]=$player->getName();
		$this->duel[$player->getName()]=$player->getName();
	}
	public function sendDuelSpectateScoreboard($player, string $type, string $queue, string $duelplayer, string $duelopponent):void{
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			$this->removeScoreboard($player);
		}
		$this->lineTitle($player, "§l§f» §l§bPandaz §l§f«");
		$this->lineCreate($player, 1, "§f-------------------- ");
		$this->lineCreate($player, 2, "§f".$type.": §b".$queue);
		$this->lineCreate($player, 3, "    ");
		$this->lineCreate($player, 4, "§b".$duelplayer." §6vs§c ".$duelopponent);
		$this->lineCreate($player, 5, "§f--------------------");
		$this->scoreboard[$player->getName()]=$player->getName();
		$this->spectator[$player->getName()]=$player->getName();
	}
	public function sendPartyDuelSpectateScoreboard($player, string $queue, string $leader):void{
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			$this->removeScoreboard($player);
		}
		$this->lineTitle($player, "§l§f» §l§bPandaz §l§f«");
		$this->lineCreate($player, 1, "§f-------------------- ");
		$this->lineCreate($player, 2, "§fParty: §b".$queue);
		$this->lineCreate($player, 3, "    ");
		$this->lineCreate($player, 4, "§6".$leader);
		$this->lineCreate($player, 5, "§f--------------------");
		$this->scoreboard[$player->getName()]=$player->getName();
		$this->spectator[$player->getName()]=$player->getName();
	}
	public function updateMainLineOnline($player){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetMain($player)){
				$this->lineRemove($player, 2);
				$this->lineCreate($player, 2, "§fOnline: §b".count($this->plugin->getServer()->getOnlinePlayers()));
			}
		}
	}
	public function updateMainLinePing($player){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetMain($player)){
				$this->lineRemove($player, 4);
				$this->lineCreate($player, 4, "§fYour Ping: §b".$player->getPing()."ms");
			}
		}
	}
	public function updateMainLineKillsDeaths($player){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetMain($player)){
				$this->lineRemove($player, 6);
				$this->lineCreate($player, 6, "§fK: §b".$this->plugin->getDatabaseHandler()->getKills($player)." §fD: §b".$this->plugin->getDatabaseHandler()->getDeaths($player));
			}
		}
	}
	public function updateMainLineKdrElo($player){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetMain($player)){
				$this->lineRemove($player, 7);
				$this->lineCreate($player, 7, "§fKDR: §b".$this->plugin->getDatabaseHandler()->getKdr($player)." §fElo: §b".$this->plugin->getDatabaseHandler()->getRankedElo($player));
			}
		}
	}
	public function updateMainLineKillstreak($player){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetMain($player)){
				$this->lineRemove($player, 8);
				$this->lineCreate($player, 8, "§fKillstreak: §b".$this->plugin->getDatabaseHandler()->getKillstreak($player)." §7(".$this->plugin->getDatabaseHandler()->getBestKillstreak($player).")");
			}
		}
	}
	public function updateBotDuelDuration($player, $duration){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetDuel($player)){
				$this->lineRemove($player, 3);
				$this->lineCreate($player, 3, "§fDuration: §6".$duration);
			}
		}
	}
	public function updateDuelDuration($player, $duration){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetDuel($player)){
				$this->lineRemove($player, 5);
				$this->lineCreate($player, 5, "§fDuration: §6".$duration);
			}
		}
	}
	public function updatePartyDuelAlive($player, $alive, $playing){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		if($this->isPlayerSetScoreboard($player)){
			if($this->isPlayerSetDuel($player)){
				$this->lineRemove($player, 4);
				$this->lineCreate($player, 4, "§fAlive: §c".$alive."/".$playing);
			}
		}
	}
	public function isPlayerSetScoreboard($player):bool{
		$name=Utils::getPlayerName($player);
		return ($name !== null) and isset($this->scoreboard[$name]);
	}
	public function isPlayerSetMain($player):bool{
		$name=Utils::getPlayerName($player);
		return ($name !== null) and isset($this->main[$name]);
	}
	public function isPlayerSetDuel($player):bool{
		$name=Utils::getPlayerName($player);
		return ($name !== null) and isset($this->duel[$name]);
	}
	public function isPlayerSetSpectator($player):bool{
		$name=Utils::getPlayerName($player);
		return ($name !== null) and isset($this->spectator[$name]);
	}
	public function lineTitle($player, string $title){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		$packet=new SetDisplayObjectivePacket();
		$packet->displaySlot="sidebar";
		$packet->objectiveName="objective";
		$packet->displayName=$title;
		$packet->criteriaName="dummy";
		$packet->sortOrder=0;
		$player->sendDataPacket($packet);
	}
	public function removeScoreboard($player){
		$player=Utils::getPlayer($player);
		$packet=new RemoveObjectivePacket();
		$packet->objectiveName="objective";
		$player->sendDataPacket($packet);
		unset($this->scoreboard[$player->getName()]);
		unset($this->main[$player->getName()]);
		unset($this->duel[$player->getName()]);
		unset($this->spectator[$player->getName()]);
	}
	public function lineCreate($player, int $line, string $content){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		$packetline=new ScorePacketEntry();
		$packetline->objectiveName="objective";
		$packetline->type=ScorePacketEntry::TYPE_FAKE_PLAYER;
		$packetline->customName=" ".$content."   ";
		$packetline->score=$line;
		$packetline->scoreboardId=$line;
		$packet=new SetScorePacket();
		$packet->type=SetScorePacket::TYPE_CHANGE;
		$packet->entries[]=$packetline;
		$player->sendDataPacket($packet);
	}
	public function lineRemove($player, int $line){
		$player=Utils::getPlayer($player);
		if(Utils::isScoreboardEnabled($player)==false){
			return;
		}
		$entry=new ScorePacketEntry();
		$entry->objectiveName="objective";
		$entry->score=$line;
		$entry->scoreboardId=$line;
		$packet=new SetScorePacket();
		$packet->type=SetScorePacket::TYPE_REMOVE;
		$packet->entries[]=$entry;
		$player->sendDataPacket($packet);
	}
}