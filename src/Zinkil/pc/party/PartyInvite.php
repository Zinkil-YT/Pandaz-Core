<?php

declare(strict_types=1);

namespace Zinkil\pc\party;

use pocketmine\Player;
use pocketmine\Server;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class PartyInvite{
	
	private $plugin;
	private $party;
	private $sender;
	private $target;
	
	public function __construct(Party $party, string $sender, string $target){
		$this->plugin=Core::getInstance();
		$this->party=$party;
		$this->sender=$sender;
		$this->target=$target;
	}
	public function getParty():?Party{
		return $this->party;
	}
	public function getSender():string{
		return $this->sender;
	}
	public function getTarget():string{
		return $this->target;
	}
	public function doesPartyExist():bool{
		return PartyManager::doesPartyExist($this->party)!==false;
	}
	public function isParty($party):bool{
		if($party instanceof Party) $party=$party->getName();
		return $party==$this->getParty()->getName();
	}
	public function isSender($player):bool{
		if($player instanceof Player) $player=$player->getName();
		return $player==$this->sender;
	}
	public function isTarget($player):bool{
		if($player instanceof Player) $player=$player->getName();
		return $player==$this->target;
	}
	public function isSenderOnline():bool{
		$player=Server::getInstance()->getPlayerExact($this->sender);
		return $player!==null;
	}
	public function isTargetOnline():bool{
		$player=Server::getInstance()->getPlayerExact($this->target);
		return $player!==null;
	}
	public function clear(){
		unset($this->plugin->partyinvites[array_search($this, $this->plugin->partyinvites)]);
	}
	public function accept(){
		$sender=Server::getInstance()->getPlayerExact($this->sender);
		$target=Server::getInstance()->getPlayerExact($this->target);
		if($sender!==null) $sender->sendMessage("§a".$target->getDisplayName()." accepted your invitation.");
		if($target!==null) $target->sendMessage("§aInvitation accepted.");
		if($this->doesPartyExist()){
			$this->party->addMember($target);
		}else{
			$target->sendMessage("§cThat party no longer exists.");
		}
		$this->clear();
	}
	public function decline(){
		$sender=Server::getInstance()->getPlayerExact($this->sender);
		$target=Server::getInstance()->getPlayerExact($this->target);
		if($sender!==null) $sender->sendMessage("§c".$target->getDisplayName()." declined your invitation.");
		if($target!==null) $target->sendMessage("§aInvitation declined.");
		$this->clear();
	}
}