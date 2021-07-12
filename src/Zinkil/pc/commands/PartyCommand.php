<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\party\PartyManager;

class PartyCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("party", $plugin);
		$this->plugin=$plugin;
		$this->setAliases(["p"]);
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player instanceof Player){
			return;
		}
		$this->plugin->getForms()->partyForm($player);
	}/*
		if(!isset($args[0])){
			$this->plugin->getForms()->partyForm($player);
			return;
		}
		switch($args[0]){
			case "list":
			if(empty($this->plugin->parties)){
				$player->sendMessage("§cThere are no parties, you can create one with /p create.");
				return;
			}
			foreach($this->plugin->parties as $party){
				$name=$party->getName()."'s Party";
				$members=count($party->getMembers());
				$parties[]="§3".$name." §7- (".$members.")§r";
			}
			$player->sendMessage(implode(", ", $parties));
			$parties=[];
			return;
			break;
			case "join":
			if($player->isInParty()){
				$player->sendMessage("§cYou are already in a party.");
				return;
			}
			if(!isset($args[1])){
				$player->sendMessage("§cProvide a party to join.");
				return;
			}
			$party=$args[1];
			if(!PartyManager::doesPartyExist($party)){
				$player->sendMessage("§cThat party does not exist.");
				return;
			}
			if(PartyManager::doesPartyExist($party)){
				if($party->isClosed()){
					$player->sendMessage("§cThat party is closed.");
					return;
				}
				$party->addMember($player);
				return;
			}
			break;
			case "leave":
			if(!$player->isInParty()){
				$player->sendMessage("§cYou are not in a party.");
				return;
			}
			if($player->isInParty()){
				if($player->getParty()->isLeader($player)){
					$player->sendMessage("§cYou cannot leave your own party.");
					return;
				}
			}
			$party->removeMember($player);
			return;
			break;
			case "create":
			if($player->isInParty()){
				$player->sendMessage("§cYou are already in a party.");
				return;
			}
			if($player->getLevel()->getName()!=Core::LOBBY){
				$player->sendMessage("§cYou cannot create a party here.");
				return;
			}
			PartyManager::createParty($player);
			$player->sendMessage("§aYour party was created.");
			break;
			case "invite":
			case "inv":
			if(!$player->isInParty()){
				$player->sendMessage("§cYou are not in a party.");
				return;
			}
			if($player->isInParty()){
				if(!$player->getParty()->isLeader($player)){
					$player->sendMessage("§cYou cannot invite members to the party.");
					return;
				}
			}
			if(!isset($args[1])){
				$player->sendMessage("§cProvide a player to invite.");
				return;
			}
			if($this->plugin->getServer()->getPlayer($args[1])===null){
				$player->sendMessage("§cPlayer not found.");
				return;
			}
			$target=Utils::getPlayer($args[1]);
			if($this->plugin->getPartyHandler()->hasInvite($target)){
				$player->sendMessage("§cThat player has already been invited to a party, try again shortly.");
				return;
			}
			if($target->isInParty()){
				$tparty=$target->getParty();
				$party=$player->getParty();
				if($tparty!=$party){
					$player->sendMessage("§cThat player is already in a party.");
					return;
				}
				if($tparty==$party){
					$player->sendMessage("§cThat player is already in your party.");
					return;
				}
			}
			if(Utils::getPlayerName($target)==Utils::getPlayerName($player)){
				$player->sendMessage("§cYou cannot invite yourself to your own party.");
				return;
			}
			$party=$player->getParty();
			$this->plugin->getPartyHandler()->sendInvite($party, $target, $player);
			return;
			break;
			case "accept":
			case "acc":
			if($player->isInParty()){
				$player->sendMessage("§cYou are in a party.");
				return;
			}
			if(!$this->plugin->getPartyHandler()->hasInvite($player)){
				$player->sendMessage("§cYou have not been invited to any parties.");
				return;
			}
			$this->plugin->getPartyHandler()->acceptInvite($player);
			return;
			break;
			case "decline":
			case "dec":
			if($player->isInParty()){
				$player->sendMessage("§cYou are in a party.");
				return;
			}
			if(!$this->plugin->getPartyHandler()->hasInvite($player)){
				$player->sendMessage("§cYou have not been invited to any parties.");
				return;
			}
			$this->plugin->getPartyHandler()->declineInvite($player);
			return;
			break;
			case "manage":
			case "man":
			if(!$player->isInParty()){
				$player->sendMessage("§cYou are not in a party.");
				return;
			}
			if($player->isInParty()){
				if(!$player->getParty()->isLeader($player)){
					$player->sendMessage("§cYou cannot manage the party.");
					return;
				}
			}
			if(!isset($args[1])){
				$player->sendMessage("§cProvide an argument");
				$player->sendMessage("§cprivacy: Open or close your party");
				$player->sendMessage("§ckick: Kick a member from your party");
				$player->sendMessage("§cdisband: Disband your party");
				return;
			}
			switch($args[1]){
				case "privacy":
				case "priv":
				if(!$this->plugin->getDatabaseHandler()->voteAccessExists($player)){
					$player->sendMessage("§cTo manage your party's privacy you must be a voter.");
					return;
				}
				if(!isset($args[2])){
					$player->sendMessage("§cProvide an argument: open:closed");
					return;
				}
				switch($args[2]){
					case "open":
					$party=$player->getParty();
					if($privacy=="open"){
						$player->sendMessage("§cYour party's privacy is already set to open.");
					}else{
						$player->sendMessage("§aYou set the party privacy to open, players can join without an invitation.");
						$this->plugin->getPartyHandler()->setPrivacy($party, "open");
					}
					return;
					break;
					case "closed":
					$party=$this->plugin->getPartyHandler()->getParty($player);
					$privacy=$this->plugin->getPartyHandler()->getPrivacy($party);
					if($privacy=="closed"){
						$player->sendMessage("§cYour party's privacy is already set to closed.");
					}else{
						$player->sendMessage("§aYou set the party privacy to closed, players now need an invitation to join.");
						$this->plugin->getPartyHandler()->setPrivacy($party, "closed");
					}
					return;
					break;
					default:
					$player->sendMessage("§cProvide a valid argument: open:closed");
					return;
				}
				break;
				case "kick":
				if(!isset($args[2])){
					$player->sendMessage("§cProvide a player to kick.");
					return;
				}
				if($this->plugin->getServer()->getPlayer($args[2])===null){
					$player->sendMessage("§cPlayer not found.");
					return;
				}
				$target=Utils::getPlayer($args[2]);
				if($target->isInParty()){
					$tparty=$this->plugin->getPartyHandler()->getParty($target);
					$party=$this->plugin->getPartyHandler()->getParty($player);
					if($tparty!=$party){
						$player->sendMessage("§cThat player is not in your party.");
						return;
					}
				}
				if(Utils::getPlayerName($target)==Utils::getPlayerName($player)){
					$player->sendMessage("§cYou cannot kick yourself from your party.");
					return;
				}
				$this->plugin->getPartyHandler()->kick($target);
				return;
				break;
				case "disband":
				if(!$player->isInParty()){
					$player->sendMessage("§cYou are not in a party.");
					return;
				}
				if(!$player->getParty()->isLeader($player)){
					$player->sendMessage("§cYou cannot disband the party.");
					return;
				}
				$this->plugin->getPartyHandler()->disband($player);
				$player->sendMessage("§aYou disbanded your party.");
				return;
				break;
				default:
				$player->sendMessage("§cProvide an argument");
				$player->sendMessage("§cprivacy: Open or close your party");
				$player->sendMessage("§ckick: Kick a member from your party");
				$player->sendMessage("§cdisband: Disband your party");
				return;
			}
			default:
			$player->sendMessage("§cProvide an argument");
			$player->sendMessage("§clist: Lists all ongoing parties");
			$player->sendMessage("§cjoin: Join a party");
			$player->sendMessage("§cleave: Leave a party");
			$player->sendMessage("§ccreate: Create a party");
			$player->sendMessage("§cinvite: Invite a member to your party");
			$player->sendMessage("§caccept: Accept an invite");
			$player->sendMessage("§cdecline: Decline an invite");
			$player->sendMessage("§cmanage: Manage your party");
			return;
		}
	}*/
}