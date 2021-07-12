<?php

declare(strict_types=1);

namespace Zinkil\pc\party;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Position;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class Party{
	
	const PREFIX="§9PARTY §8»§r ";
	
	const LEADER="Leader";
	const MEMBER="Member";
	
	const IDLE=0;
	const DUEL=1;
	
	private $plugin;
	private $name;
	private $leader;
	public $members=[];
	private $capacity=8;
	private $closed=false;
	private $status=0;
	
	public function __construct(string $name, string $leader, array $members, int $capacity, bool $closed, int $status){
		$this->plugin=Core::getInstance();
		$this->name=$name;
		$this->leader=$leader;
		$this->members=$members;
		$this->capacity=$capacity;
		$this->closed=$closed;
		$this->status=$status;
	}
	public function getName():string{
		return $this->name;
	}
	public function getLeader():string{
		return $this->leader;
	}
	public function getMembers():array{
		return $this->members;
	}
	public function getCapacity():int{
		return $this->capacity;
	}
	public function getMembersOnline():array{
		$online=[];
		foreach($this->members as $member){
			$player=Server::getInstance()->getPlayerExact($member);
			if($player!==null){
				$online[]=$player->getName();
			}
		}
		return $online;
	}
	public function isClosed():bool{
		return $this->closed===true;
	}
	public function isFull():bool{
		return count($this->members) >= $this->capacity;
	}
	public function getStatus():int{
		return $this->status;
	}
	public function setStatus(int $status){
		$this->status=$status;
	}
	public function isLeader($player):bool{
		if($player instanceof Player) $player=$player->getName();
		return $player==$this->leader;
	}
	public function isMember($player):bool{
		if($player instanceof Player) $player=$player->getName();
		return in_array($player, $this->members);
	}
	public function setLeader($player){
		if($player instanceof Player) $player=$player->getName();
		$this->leader=$player;
	}
	public function setMembers(array $members){
		$this->members=[];
		$this->members=$members;
	}
	public function setClosed(){
		$this->closed=true;
	}
	public function setOpen(){
		$this->closed=false;
	}
	public function addMember(Player $player){
		if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
			$this->plugin->getDuelHandler()->removePlayerFromQueue($player);
		}
		$this->sendMessage($player->getDisplayName()." has joined the party.");
		$this->members[]=$player->getName();
		if($player instanceof CPlayer) $player->setParty($this);
		if($player instanceof CPlayer) $player->setPartyRank(self::MEMBER);
		if($player instanceof CPlayer) $player->sendMessage("§aYou joined the party.");
	}
	public function removeMember(Player $player){
		unset($this->members[array_search($player->getName(), $this->members)]);
		$this->sendMessage($player->getDisplayName()." has left the party.");
		if($player instanceof CPlayer) $player->setParty(null);
		if($player instanceof CPlayer) $player->setPartyRank(null);
		if($player instanceof CPlayer) $player->sendMessage("§aYou left the party.");
	}
	public function kickMember(Player $player){
		unset($this->members[array_search($player->getName(), $this->members)]);
		if($player instanceof CPlayer) $player->setParty(null);
		if($player instanceof CPlayer) $player->setPartyRank(null);
		if($player instanceof CPlayer) $player->sendMessage("§cYou were kicked from the party.");
		$this->sendMessage($player->getDisplayName()." was kicked from the party.");
	}
	public function sendMessage(string $message){
		foreach($this->members as $member){
			$member=Server::getInstance()->getPlayerExact($member);
			if($member instanceof Player){
				$member->sendMessage(self::PREFIX.$message);
			}
		}
	}
	public function disband(){
		$leader=Server::getInstance()->getPlayerExact($this->leader);
		if($leader!==null){
			$leader->sendMessage("§aYou disbanded your party.");
			unset($this->members[array_search($leader->getName(), $this->members)]);
			if($leader instanceof CPlayer) $leader->setParty(null);
			if($leader instanceof CPlayer) $leader->setPartyRank(null);
		}
		$this->sendMessage($this->leader." disbanded the party.");
		foreach($this->members as $member){
			$member=Server::getInstance()->getPlayerExact($member);
			if($member instanceof Player){
				$member->setParty(null);
				$member->setPartyRank(null);
			}
		}
		unset($this->plugin->parties[array_search($this, $this->plugin->parties)]);
		foreach(PartyManager::getInvitesFromParty($this) as $invites){
			$invites->clear();
		}
	}
}