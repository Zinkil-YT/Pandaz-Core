<?php

declare(strict_types=1);

namespace Zinkil\pc;

use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\party\{PartyManager, Party};
use Zinkil\pc\forms\{SimpleForm, ModalForm, CustomForm};

class Forms{
	
	private $plugin;
	
	private $targetParty=[];
	
	private $targetPlayer=[];
	
	private $targetInvite=[];
	
	public function __construct(){
		$this->plugin=Core::getInstance();
	}
	public function partyForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "create":
				if($player->isInParty()){
					$player->sendMessage("§cYou are already in a party.");
					return;
				}
				if($player->getLevel()->getName()!=Core::LOBBY){
					$player->sendMessage("§cYou cannot create a party here.");
					return;
				}
				if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
					$this->plugin->getDuelHandler()->removePlayerFromQueue($player);
				}
				PartyManager::createParty($player);
				break;
				case "invites":
				$this->invitesForm($player);
				break;
				case "list":
				$this->partiesForm($player);
				break;
				case "duel":
				if($this->plugin->getDuelHandler()->isInPartyDuel($player)){ //make a proper check to see if the specific party is dueling
					$player->sendMessage("§cYou cannot start another duel.");
					return;
				}
				if(1 >= count($player->getParty()->getMembers())){
					$player->sendMessage("§cYour party must have at least 2 players to start a duel.");
					return;
				}
				$this->partyDuelForm($player);
				break;
				case "members":
				$this->partyMembersForm($player);
				break;
				case "leave":
				if($this->plugin->getDuelHandler()->isInPartyDuel($player)){
					$player->sendMessage("§cYou cannot leave the party while in a duel.");
					return;
				}
				$player->getParty()->removeMember($player);
				break;
				case "invite":
				$this->playerListForm($player);
				break;
				case "manage":
				$this->partyManageForm($player);
				break;
				case "disband":
				$player->getParty()->disband();
				break;
			}
		});
		$form->setTitle("§l§cParty");
		$party=$player->getParty();
		if(!$player->isInParty()){
			$form->addButton("Create", -1, "", "create");
			$form->addButton("Invites", -1, "", "invites");
		}
		if($player->isInParty()){
			if($party->isLeader($player)) $form->addButton("Duel", -1, "", "duel");
			$form->addButton("Members", -1, "", "members");
			if(!$party->isLeader($player)) $form->addButton("Leave", -1, "", "leave");
			if($party->isLeader($player)) $form->addButton("Invite", -1, "", "invite");
			if($party->isLeader($player)) $form->addButton("Manage", -1, "", "manage");
			if($party->isLeader($player)) $form->addButton("Disband", -1, "", "disband");
		}
		$form->addButton("List", -1, "", "list");
		$player->sendForm($form);
	}
	public function invitesForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "exit":
				$this->partyForm($player);
				unset($this->targetInvite[Utils::getPlayerName($player)]);
				break;
				case 0:
				$this->targetInvite[Utils::getPlayerName($player)]=$data;
				$invite=$this->targetInvite[Utils::getPlayerName($player)];
				if($player->isInParty()){
					$player->sendMessage("§cYou are already in a party.");
					return;
				}
				$this->manageInviteForm($player);
				break;
			}
		});
		$form->setTitle("§l§cInvites");
		foreach(PartyManager::getInvites($player) as $invite){
			$party=$invite->getParty()->getName()."'s Party";
			$form->addButton($party, -1, "", $invite->getParty()->getName());
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function manageInviteForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "exit":
				$this->invitesForm($player);
				break;
				case "accept":
				$invite=PartyManager::getInvite($this->targetInvite[Utils::getPlayerName($player)]);
				$party=$invite->getParty();
				if($party->isFull()){
					$player->sendMessage("§cThat party is full.");
					return;
				}
				$invite->accept();
				break;
				case "decline":
				$invite=PartyManager::getInvite($this->targetInvite[Utils::getPlayerName($player)]);
				$invite->decline();
				break;
			}
			unset($this->targetPlayer[Utils::getPlayerName($player)]);
		});
		$form->setTitle("§l§cManage Invitation");
		$form->addButton("Accept", -1, "", "accept");
		$form->addButton("Decline", -1, "", "decline");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function partyDuelForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0:
				$this->mode="NoDebuff";
				break;
				case 1:
				$this->mode="Gapple";
				break;
				case 2:
				$this->mode="Soup";
				break;
				case 3:
				$this->mode="BuildUHC";
				break;
			}
			switch($data[1]){
				case 0:
				$this->specs=false;
				break;
				case 1:
				$this->specs=true;
				break;
			}
			$this->plugin->getDuelHandler()->startPartyDuel($player->getParty(), $player->getParty()->getMembers(), $this->mode, $this->specs);
		});
		$data[0]=["NoDebuff", "Gapple", "Soup", "BuildUHC"];
		$form->setTitle("§l§cSetup");
		$form->addDropdown("Choose a mode", $data[0], 0);
		$form->addToggle("Allow spectators", false, null);
		$player->sendForm($form);
	}
	public function partiesForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "exit":
				$this->partyForm($player);
				break;
				case 0:
				$this->targetParty[Utils::getPlayerName($player)]=$data;
				$p=$this->targetParty[Utils::getPlayerName($player)];
				$party=PartyManager::getParty($p);
				if($party===null) return;
				if($player->isInParty()){
					$player->sendMessage("§cYou are already in a party.");
					return;
				}
				if(!PartyManager::doesPartyExist($party)){
					$player->sendMessage("§cThat party does not exist.");
					return;
				}
				if($party->isClosed()){
					$player->sendMessage("§cThat party is closed.");
					return;
				}
				if($party->isFull()){
					$player->sendMessage("§cThat party is full.");
					return;
				}
				$party->addMember($player);
				break;
			}
			unset($this->targetParty[Utils::getPlayerName($player)]);
		});
		$form->setTitle("§l§cParties");
		foreach($this->plugin->parties as $party){
			$name=$party->getName()."'s Party";
			$members=count($party->getMembers());
			$capacity=$party->getCapacity();
			$form->addButton($name." (".$members."/".$capacity.")", -1, "", $party->getName());
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function partyMembersForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "exit":
				$this->partyForm($player);
				unset($this->targetPlayer[Utils::getPlayerName($player)]);
				break;
				case 0:
				$party=$player->getParty();
				if(!$party->isLeader($player)){
					$player->sendMessage("§cYou cannot manage party members.");
					return;
				}
				if($player->getName()==$data){
					$player->sendMessage("§cYou cannot manage yourself.");
					return;
				}
				$this->targetPlayer[Utils::getPlayerName($player)]=$data;
				$this->managePartyMemberForm($player);
				break;
			}
		});
		$party=$player->getParty();
		$members=count($party->getMembers());
		$capacity=$party->getCapacity();
		$form->setTitle("§l§cMembers (".$members."/".$capacity.")");
		foreach($party->getMembers() as $members){
			$players=$this->plugin->getServer()->getPlayerExact($members);
			$form->addButton($members."\n".$players->getPartyRank(), -1, "", $members);
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function managePartyMemberForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "exit":
				$this->partyMembersForm($player);
				break;
				case "kick":
				$party=$player->getParty();
				$target=$this->plugin->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player) $party->kickMember($target);
				break;
			}
			unset($this->targetPlayer[Utils::getPlayerName($player)]);
		});
		$form->setTitle("Manage ".$this->targetPlayer[Utils::getPlayerName($player)]);
		$form->addButton("Kick", -1, "", "kick");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function playerListForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case "exit":
				$this->partyForm($player);
				break;
				case 0:
				$this->targetPlayer[Utils::getPlayerName($player)]=$data;
				$target=Utils::getPlayer($this->targetPlayer[Utils::getPlayerName($player)]);
				$party=$player->getParty();
				if($target===null){
					$player->sendMessage("§cThis player is offline.");
					return;
				}
				if($target->getName()==$player->getName()){
					$player->sendMessage("§cYou cannot invite yourself.");
					return;
				}
				if(PartyManager::hasInvite($target, $party)){
					$player->sendMessage("§cThis player has already been invited to your party.");
					return;
				}
				if($target->isInParty()){
					$player->sendMessage("§cThis player is already in a party.");
					return;
				}
				PartyManager::invitePlayer($party, $player, $target);
				break;
			}
			unset($this->targetPlayer[Utils::getPlayerName($player)]);
		});
		$form->setTitle("§l§cOnline Players");
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $players){
			$form->addButton($players->getDisplayName(), -1, "", $players->getName());
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function partyManageForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			if($data===null) return;
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://open
				$party=$player->getParty();
				if(!$party->isClosed()) return;
				$party->setOpen();
				$player->sendMessage("§aYour party is now open, players can join.");
				break;
				case 1://closed
				$party=$player->getParty();
				if($party->isClosed()) return;
				$party->setClosed();
				$player->sendMessage("§aYour party is now closed, players can only join via invitation.");
				break;
			}
		});
		$form->setTitle("§l§cManage Party");
		if($player->getParty()->isClosed()){
			$form->addToggle("Closed", true, null);//data[0]
		}else{
			$form->addToggle("Open", false, null);//data[0]
		}
		$player->sendForm($form);
	}
}