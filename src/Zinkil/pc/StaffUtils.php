<?php

declare(strict_types=1);

namespace Zinkil\pc;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Kits;
use Zinkil\pc\Utils;
use Zinkil\pc\forms\{SimpleForm, ModalForm, CustomForm};

class StaffUtils{
	
	public $targetPlayer=[];
	
	public function staffMode(Player $player, $bool=false){
		if($bool===true){
			$player->setStaffMode(true);
			Kits::sendKit($player, "staff");
			$player->sendMessage("§aYou entered staff mode, you are required to stay in either spectator mode, or vanish.");
			$message=$this->sendStaffNoti("staffmodeon");
			$message=str_replace("{name}", Utils::getPlayerName($player), $message);
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
				if($online->hasPermission("pc.staff.notifications")){
					$online->sendMessage($message);
				}
			}
		}else{
			$player->setStaffMode(false);
			$player->sendTo(0, true);
			$player->sendMessage("§aYou left staff mode.");
			$message=$this->sendStaffNoti("staffmodeoff");
			$message=str_replace("{name}", Utils::getPlayerName($player), $message);
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
				if($online->hasPermission("pc.staff.notifications")){
					$online->sendMessage($message);
				}
			}
		}
	}
	public function vanish(Player $player, $bool=false){
		if($bool===true){
			$player->setVanished(true);
			Core::getInstance()->getServer()->broadcastMessage("§f(§c-§f) §c".$player->getDisplayName());
			$player->sendMessage("§aYou are now vanished.");
			$player->setGamemode(3);
			$message=Core::getInstance()->getStaffUtils()->sendStaffNoti("vanishon");
			$message=str_replace("{name}", $player->getName(), $message);
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
				if($online->hasPermission("pc.staff.notifications")){
					$online->sendMessage($message);
				}
			}
		}else{
			$player->setVanished(false);
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
				$online->showPlayer($player);
			}
			$player->sendMessage("§aYou are no longer vanished.");
			$player->setGamemode(2);
			Core::getInstance()->getServer()->broadcastMessage("§f(§a+§f) §a".$player->getDisplayName());
			$message=Core::getInstance()->getStaffUtils()->sendStaffNoti("vanishoff");
			$message=str_replace("{name}", $player->getName(), $message);
			foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
				if($online->hasPermission("pc.staff.notifications")){
					$online->sendMessage($message);
				}
			}
		}
	}
	public function messages(Player $player, $bool=false){
		if($bool===true){
			$player->setMessages(true);
			$player->sendMessage("§aYou will now see private messages.");
		}else{
			$player->setMessages(false);
			$player->sendMessage("§aYou will no longer see private messages.");
		}
	}
	public function anticheat(Player $player, $bool=false){
		if($bool===true){
			$player->setAntiCheat(true);
			$player->sendMessage("§aYou will now see the anti-cheat.");
		}else{
			$player->setAntiCheat(false);
			$player->sendMessage("§aYou will no longer see the anti-cheat.");
		}
	}
	public function coords(Player $player, $bool=false){
		if($bool===true){
			$player->setCoordins(true);
			$packet=new GameRulesChangedPacket();
			$packet->gameRules=["showcoordinates" => [1, true]];
			$player->dataPacket($packet);
			$player->sendMessage("§aYou will now see your coords.");
		}else{
			$player->setCoordins(false);
			$packet=new GameRulesChangedPacket();
			$packet->gameRules=["showcoordinates" => [1, false]];
			$player->dataPacket($packet);
			$player->sendMessage("§aYou will no longer see your coords.");
		}
	}
	public function sendStaffNoti($type){
		switch($type){
			case "temprankchange":
			$message="§o§7[§b{name}: §7updated {target}'s rank from {oldrank} to {newrank} for {days} days]";
			return $message;
			break;
			case "rankchange":
			$message="§o§7[§b{name}: §7updated {target}'s rank from {oldrank} to {newrank}]";
			return $message;
			break;
			case "gamemodechange":
			$message="§o§7[§b{name}: §7updated their gamemode to {newgamemode}]";
			return $message;
			break;
			case "gamemodechangeother":
			$message="§o§7[§b{name}: §7updated {target}'s gamemode to {newgamemode}]";
			return $message;
			break;
			case "internalerror":
			$message="§o§7[§b{name}: §7disconnected due to an internal server error]";
			return $message;
			break;
			case "timeout":
			$message="§o§7[§b{name}: §7disconnected due to timeout]";
			return $message;
			break;
			case "disguiseon":
			$message="§o§7[§b{name}: §7entered a disguise as {disguise}]";
			return $message;
			break;
			case "disguiseoff":
			$message="§o§7[§b{name}: §7left their disguise as {disguise}]";
			return $message;
			break;
			case "vanishon":
			$message="§o§7[§b{name}: §7entered vanish]";
			return $message;
			break;
			case "vanishoff":
			$message="§o§7[§b{name}: §7left vanish]";
			return $message;
			break;
			case "staffmodeon":
			$message="§o§7[§b{name}: §7entered staff mode]";
			return $message;
			break;
			case "staffmodeoff":
			$message="§o§7[§b{name}: §7left staff mode]";
			return $message;
			break;
			case "freeze":
			$message="§o§7[§b{name}: §7froze {target}]";
			return $message;
			break;
			case "unfreeze":
			$message="§o§7[§b{name}: §7unfroze {target}]";
			return $message;
			break;
			case "tpall":
			$message="§o§7[§b{name}: §7teleported all players to them]";
			return $message;
			break;
			case "rankexpire":
			$message="§o§7[§b{target}: §7{rank} rank expired]";
			return $message;
			break;
			case "voteraccessexpire":
			$message="§o§7[§b{target}: §7voter access expired]";
			return $message;
			break;
			case "mute":
			$message="§o§7[§b{name}: §7muted {target} for {reason}]";
			return $message;
			break;
			case "unmute":
			$message="§o§7[§b{name}: §7unmuted {target}]";
			return $message;
			break;
			case "temporaryban":
			$message="§o§7[§b{name}: §7temporarily banned {target} for {reason}]";
			return $message;
			break;
			case "temporaryunban":
			$message="§o§7[§b{name}: §7(temporary) unbanned {target}]";
			return $message;
			break;
			case "permanentban":
			$message="§o§7[§b{name}: §7permanently banned {target} for {reason}]";
			return $message;
			break;
			case "permanentunban":
			$message="§o§7[§b{name}: §7(permanent) unbanned {target}]";
			return $message;
			break;
			case "autounmute":
			$message="§o§7[§b{target}: §7mute expired]";
			return $message;
			break;
			case "autotemporaryunban":
			$message="§o§7[§b{target}: §7ban time expired]";
			return $message;
			break;
			case "autopermanentban":
			$message="§o§7[§b{target}: §7automatic permanent ban, player reached the maximum warn points]";
			return $message;
			break;
			default:
			return;
		}
	}
	public function sendStaffAlert($type){
		switch($type){
			case "autoclick":
			$message="§8[STAFF] §b{name} §eflagged for §4AUTO-CLICK. §f(§6{details} CPS§f)";
			return $message;
			break;
			case "ping":
			$message="§8[STAFF] §b{name} §ekicked for a long period of §4HIGH PING. §f(§6{details}ms§f)";
			return $message;
			break;
			case "reach":
			$message="§8[STAFF] §b{name} §eflagged for §4REACH. §f(§6{details} blocks§f)";
			return $message;
			break;
			case "highjump":
			$message="§8[STAFF] §b{name} flagged for HIGHJUMP.";
			return $message;
			break;
			case "killaura":
			$message="§8[STAFF] §b{name} flagged for KILLAURA.";
			return $message;
			break;
			default:
			return;
		}
	}
	public function teleportForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "lobby":
				$player->sendTo(0, false);
				break;
				case "nodebuff":
				$player->sendTo(1, false, false);
				break;
				case "gapple":
				$player->sendTo(2, false, false);
				break;
				case "combo":
				$player->sendTo(3, false, false);
				break;
				case "battlefield":
				$player->sendTo(4, false, false);
				break;
				case "offline":
				$player->sendMessage("§cThis arena is currently offline.");
				break;
				case "wip":
				$player->sendMessage("§cThis arena is currently being fixed.");
				break;
			}
		});
		$lobby=Core::getInstance()->getServer()->getLevelByName("lobby");
		$nodebuff=Core::getInstance()->getServer()->getLevelByName("nodebuff");
		$gapple=Core::getInstance()->getServer()->getLevelByName("gapple");
		$combo=Core::getInstance()->getServer()->getLevelByName("combo");
		$battlefield=Core::getInstance()->getServer()->getLevelByName("battlefield");
		if(!Core::getInstance()->getServer()->isLevelLoaded("nodebuff")){
			$details1="Players: 0 Status: §cOffline";
			$c1="offline";
			}else{
				$details1="Players: ".count($nodebuff->getPlayers())." Status: §aOnline";
				$c1="nodebuff";
		}
		if(!Core::getInstance()->getServer()->isLevelLoaded("gapple")){
			$details2="Players: 0 Status: §cOffline";
			$c2="offline";
			}else{
				$details2="Players: ".count($gapple->getPlayers())." Status: §aOnline";
				$c2="gapple";
		}
		if(!Core::getInstance()->getServer()->isLevelLoaded("combo")){
			$details3="Players: 0 Status: §cOffline";
			$c3="offline";
			}else{
				$details3="Players: ".count($combo->getPlayers())." Status: §aOnline";
				$c3="combo";
		}
		if(!Core::getInstance()->getServer()->isLevelLoaded("battlefield")){
			$details5="Players: 0 Status: §cOffline";
			$c5="offline";
			}else{
				$details5="Players: ".count($battlefield->getPlayers())." Status: §aOnline";
				$c5="battlefield";
		}
		if(!Core::getInstance()->getServer()->isLevelLoaded("lobby")){
			$details6="Players: 0 Status: §cOffline";
			$c6="offline";
			}else{
				$details6="Players: ".count($lobby->getPlayers())." Status: §aOnline";
				$c6="lobby";
		}
		$form->setTitle("Teleport");
		$form->setContent("§3There are ".count(Core::getInstance()->getServer()->getOnlinePlayers())." player(s) online.");
		$form->addButton("NoDebuff\n".$details1, 0, "textures/ui/worldsIcon", $c1);
		$form->addButton("Gapple\n".$details2, 0, "textures/ui/worldsIcon", $c2);
		$form->addButton("Combo\n".$details3, 0, "textures/ui/worldsIcon", $c3);
		$form->addButton("Battlefield\n".$details5, 0, "textures/ui/worldsIcon", $c5);
		$form->addButton("Lobby\n".$details6, 0, "textures/ui/worldsIcon", $c6);
		$player->sendForm($form);
	}
	public function staffPortalForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				return;
				break;
				case "pban":
				$this->permanentBanHomeForm($player);
				break;
				case "tban":
				$this->temporaryBanHomeForm($player);
				break;
				case "mute":
				$this->muteHomeForm($player);
				break;
				case "viewinfo":
				$this->infoHomeForm($player);
				break;
			}
		});
		$form->setTitle("Staff Portal");
		if($player->hasPermission("pc.command.pban")){
			$form->addButton("Permanently Ban a Player", -1, "", "pban");
		}
		if($player->hasPermission("pc.command.tban")){
			$form->addButton("Temporarily Ban a Player", -1, "", "tban");
		}
		if(!$player->hasPermission("pc.command.mute")){
			$form->addButton("Mute a Player", -1, "", "mute");
		}
		$player->sendForm($form);
	}
	public function permanentBanHomeForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->staffPortalForm($player);
				break;
				case "online":
				$this->playerListForm($player, "pban");
				break;
				case "search":
				$this->playerFindForm($player, "pban");
				break;
				case "existing":
				$this->existingPermanentBansForm($player);
				break;
			}
		});
		$form->setTitle("§l§cPermanent Ban");
		$form->addButton("Online Players", -1, "", "online");
		$form->addButton("Search For a Player", -1, "", "search");
		$form->addButton("Existing Punishments", -1, "", "existing");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
		$player->removeAllWindows();
	}
	public function temporaryBanHomeForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->staffPortalForm($player);
				break;
				case "online":
				$this->playerListForm($player, "tban");
				break;
				case "search":
				$this->playerFindForm($player, "tban");
				break;
				case "existing":
				$this->existingTemporaryBansForm($player);
				break;
			}
		});
		$form->setTitle("§l§cTemporary Ban");
		$form->addButton("Online Players", -1, "", "online");
		$form->addButton("Search For a Player", -1, "", "search");
		$form->addButton("Existing Punishments", -1, "", "existing");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function muteHomeForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->staffPortalForm($player);
				break;
				case "online":
				$this->playerListForm($player, "mute");
				break;
				case "search":
				$this->playerFindForm($player, "mute");
				break;
				case "existing":
				$this->existingMutesForm($player);
				break;
			}
		});
		$form->setTitle("§l§cMute");
		$form->addButton("Online Players", -1, "", "online");
		$form->addButton("Search For a Player", -1, "", "search");
		$form->addButton("Existing Punishments", -1, "", "existing");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function playerListForm(Player $player, string $type):void{
		$this->type=$type;
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null){
				return;
			}
			switch($data){
				case "exit":
				switch($this->type){
					case "pban":
					$this->permanentBanHomeForm($player);
					break;
					case "tban":
					$this->temporaryBanHomeForm($player);
					break;
					case "mute":
					$this->muteHomeForm($player);
					break;
					case "info":
					$this->infoHomeForm($player);
					break;
					default:
					return;
				}
				break;
				case 0:
				$this->targetPlayer[Utils::getPlayerName($player)]=$data;
				$target=$this->targetPlayer[Utils::getPlayerName($player)];
				if(Utils::getPlayerName($target)==Utils::getPlayerName($player)){
					$player->sendMessage("§cYou cannot punish yourself.");
					return;
				}
				if(Utils::getPlayer($target)->isOp()){
					$player->sendMessage("§cYou cannot punish this player.");
					return;
				}
				if($data!==null){
					switch($this->type){
						case "pban":
						$this->permanentBanForm($player);
						break;
						case "tban":
						$this->temporaryBanForm($player);
						break;
						case "mute":
						$this->muteForm($player);
						break;
						case "info":
						$this->infoForm($player);
						break;
						default:
						return;
					}
				}
				break;
			}
		});
		$form->setTitle("§l§cOnline Players");
		foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $players){
			$form->addButton($players->getName(), -1, "", $players->getName());
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function playerFindForm(Player $player, string $type):void{
		$this->type=$type;
		$form=new CustomForm(function(Player $player, $data=null):void{
			if($data===null){
				return;
			}
			switch($data){
				case 0:
				return;
				break;
				}
				if($data[0]==null){
					$player->sendMessage("§cYou must provide a name.");
					return;
				}
				switch($this->type){
					case "pban":
					$this->searchedPermanentBanForm($player, $data[0]);
					break;
					case "tban":
					$this->searchedTemporaryBanForm($player, $data[0]);
					break;
					case "mute":
					$this->searchedMuteForm($player, $data[0]);
					break;
					case "info":
					$this->searchedInfoForm($player, $data[0]);
					break;
					default:
					return;
				}
		});
		$form->setTitle("§l§cFind a Player");
		$form->addInput("Exact Name", "", null, null);
		$player->sendForm($form);
	}
	public function permanentBanForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->permanentBanHomeForm($player);
				break;
				case "confirm":
				$reason="Executive Choice";
				$staff=Utils::getPlayerName($player);
				Core::getInstance()->getDatabaseHandler()->permanentlyBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou permanently banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.".");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was permanently banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("permanentban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
			}
			unset($this->targetPlayer[Utils::getPlayerName($player)]);
		});
		$form->setTitle("§l§cPermanently ban ".$this->targetPlayer[Utils::getPlayerName($player)]);
		$form->addButton("Confirm", -1, "", "confirm");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function temporaryBanForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->temporaryBanHomeForm($player);
				break;
				case "unfairadvantage":
				$reason="Unfair Advantage";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=5;
				$now=time();
				$day=7 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "evasion":
				$reason="Punishment Evasion";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=10;
				$now=time();
				$day=14 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "excessivetoxicity":
				$reason="Excessive Toxicity";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=3;
				$now=time();
				$day=1 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "exploitation":
				$reason="Exploitation";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=5;
				$now=time();
				$day=3 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "impersonation":
				$reason="Impersonation";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=3;
				$now=time();
				$day=1 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "teaming":
				$reason="Teaming";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=2;
				$now=time();
				$day=0 * 86400;
				$hour=6 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "advertisement":
				$reason="Advertisement";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=2;
				$now=time();
				$day=0 * 86400;
				$hour=12 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->targetPlayer[Utils::getPlayerName($player)]." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
			}
			$warnpoints=Core::getInstance()->getDatabaseHandler()->getWarnPoints($this->targetPlayer[Utils::getPlayerName($player)]);
			if($warnpoints>=20){
				Core::getInstance()->getDatabaseHandler()->permanentlyBanPlayer($this->targetPlayer[Utils::getPlayerName($player)], "Maximum Warn Points Reached", "Server");
				$message=$this->sendStaffNoti("autopermanentban");
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
			}
			unset($this->targetPlayer[Utils::getPlayerName($player)]);
		});
		$form->setTitle("§l§cAction for §r".$this->targetPlayer[Utils::getPlayerName($player)]);
		$form->addButton("Unfair Advantage", -1, "", "unfairadvantage");
		$form->addButton("Punishment Evasion", -1, "", "evasion");
		$form->addButton("Excessive Toxicity", -1, "", "excessivetoxicity");
		$form->addButton("Exploitation", -1, "", "exploitation");
		$form->addButton("Impersonation", -1, "", "impersonation");
		$form->addButton("Teaming", -1, "", "teaming");
		$form->addButton("Advertisement", -1, "", "advertisement");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function muteForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->muteHomeForm($player);
				break;
				case "excessivetoxicity":
				$reason="Excessive Toxicity";
				$staff=Utils::getPlayerName($player);
				$now=time();
				$day=0 * 86400;
				$hour=3 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->mutePlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->sendMessage("§cYou have been muted for 3 hours.");
				}
				$player->sendMessage("§aYou muted ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". Duration - 3H.");
				$message=$this->sendStaffNoti("mute");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "impersonation":
				$reason="Impersonation";
				$staff=Utils::getPlayerName($player);
				$now=time();
				$day=0 * 86400;
				$hour=1 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->mutePlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->sendMessage("§cYou have been muted for 1 hour.");
				}
				$player->sendMessage("§aYou muted ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". Duration - 1H.");
				$message=$this->sendStaffNoti("mute");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "advertisement":
				$reason="Advertisement";
				$staff=Utils::getPlayerName($player);
				$now=time();
				$day=0 * 86400;
				$hour=8 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->mutePlayer($this->targetPlayer[Utils::getPlayerName($player)], $reason, $duration, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->targetPlayer[Utils::getPlayerName($player)]);
				if($target instanceof Player){
					$target->sendMessage("§cYou have been muted for 8 hours.");
				}
				$player->sendMessage("§aYou muted ".$this->targetPlayer[Utils::getPlayerName($player)]." for ".$reason.". Duration - 8H.");
				$message=$this->sendStaffNoti("mute");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->targetPlayer[Utils::getPlayerName($player)], $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
			}
			unset($this->targetPlayer[Utils::getPlayerName($player)]);
		});
		$form->setTitle("§l§cAction for §r".$this->targetPlayer[Utils::getPlayerName($player)]);
		$form->addButton("Excessive Toxicity", -1, "", "excessivetoxicity");
		$form->addButton("Impersonation", -1, "", "impersonation");
		$form->addButton("Advertisement", -1, "", "advertisement");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function searchedPermanentBanForm(Player $player, $searchedtarget):void{
		$this->searchedtarget=$searchedtarget;
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->permanentBanHomeForm($player);
				break;
				case "confirm":
				$reason="Executive Choice";
				$staff=Utils::getPlayerName($player);
				Core::getInstance()->getDatabaseHandler()->permanentlyBanPlayer($this->searchedtarget, $reason, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been permanently banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou permanently banned ".$this->searchedtarget." for ".$reason.".");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was permanently banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("permanentban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
			}
		});
		$form->setTitle("§l§cPermanently ban §r".$this->searchedtarget);
		$form->addButton("Confirm", -1, "", "confirm");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function searchedTemporaryBanForm(Player $player, $searchedtarget):void{
		$this->searchedtarget=$searchedtarget;
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->temporaryBanHomeForm($player);
				break;
				case "unfairadvantage":
				$reason="Unfair Advantage";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=5;
				$now=time();
				$day=7 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "evasion":
				$reason="Punishment Evasion";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=10;
				$now=time();
				$day=14 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "excessivetoxicity":
				$reason="Excessive Toxicity";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=3;
				$now=time();
				$day=1 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "exploitation":
				$reason="Exploitation";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=5;
				$now=time();
				$day=3 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "impersonation":
				$reason="Impersonation";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=3;
				$now=time();
				$day=1 * 86400;
				$hour=0 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "teaming":
				$reason="Teaming";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=2;
				$now=time();
				$day=0 * 86400;
				$hour=6 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "advertisement":
				$reason="Advertisement";
				$staff=Utils::getPlayerName($player);
				$pointsgiven=2;
				$now=time();
				$day=0 * 86400;
				$hour=12 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->temporaryBanPlayer($this->searchedtarget, $reason, $duration, $player, $pointsgiven);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->kick("§cYou have been temporarily banned.\n§fReason: ".$reason."\n§fContact us: ".Core::getInstance()->getDiscord(), false);
				}
				$player->sendMessage("§aYou temporarily banned ".$this->searchedtarget." for ".$reason.". (+".$pointsgiven." WPs)");
				Core::getInstance()->getServer()->broadcastMessage("§4".$this->searchedtarget." was temporarily banned by ".Utils::getPlayerName($player).".");
				$message=$this->sendStaffNoti("temporaryban");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
			}
			$warnpoints=Core::getInstance()->getDatabaseHandler()->getWarnPoints($this->searchedtarget);
			if($warnpoints>=20){
				Core::getInstance()->getDatabaseHandler()->permanentlyBanPlayer($this->searchedtarget, "Maximum Warn Points Reached", "Server");
				$message=$this->sendStaffNoti("autopermanentban");
				$message=str_replace("{target}", $this->searchedtarget, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
			}
		});
		$form->setTitle("§l§cAction for §r".$this->searchedtarget);
		$form->addButton("Unfair Advantage", -1, "", "unfairadvantage");
		$form->addButton("Punishment Evasion", -1, "", "evasion");
		$form->addButton("Excessive Toxicity", -1, "", "excessivetoxicity");
		$form->addButton("Exploitation", -1, "", "exploitation");
		$form->addButton("Impersonation", -1, "", "impersonation");
		$form->addButton("Teaming", -1, "", "teaming");
		$form->addButton("Advertisement", -1, "", "advertisement");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function searchedMuteForm(Player $player, $searchedtarget):void{
		$this->searchedtarget=$searchedtarget;
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->muteHomeForm($player);
				break;
				case "excessivetoxicity":
				$reason="Excessive Toxicity";
				$staff=Utils::getPlayerName($player);
				$now=time();
				$day=0 * 86400;
				$hour=3 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->mutePlayer($this->searchedtarget, $reason, $duration, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->sendMessage("§cYou have been muted for 3 hours.");
				}
				$player->sendMessage("§aYou muted ".$this->searchedtarget." for ".$reason.". Duration - 3H.");
				$message=$this->sendStaffNoti("mute");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "impersonation":
				$reason="Impersonation";
				$staff=Utils::getPlayerName($player);
				$now=time();
				$day=0 * 86400;
				$hour=1 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->mutePlayer($this->searchedtarget, $reason, $duration, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->sendMessage("§cYou have been muted for 1 hours");
				}
				$player->sendMessage("§aYou muted ".$this->searchedtarget." for ".$reason.". Duration - 1H.");
				$message=$this->sendStaffNoti("mute");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
				case "advertisement":
				$reason="Advertisement";
				$staff=Utils::getPlayerName($player);
				$now=time();
				$day=0 * 86400;
				$hour=8 * 3600;
				$minute=0 * 60;
				$duration=$now + $day + $hour + $minute;
				Core::getInstance()->getDatabaseHandler()->mutePlayer($this->searchedtarget, $reason, $duration, $player);
				$target=Core::getInstance()->getServer()->getPlayerExact($this->searchedtarget);
				if($target instanceof Player){
					$target->sendMessage("§cYou have been muted for 8 hours.");
				}
				$player->sendMessage("§aYou muted ".$this->searchedtarget." for ".$reason.". Duration - 8H.");
				$message=$this->sendStaffNoti("mute");
				$message=str_replace("{name}", Utils::getPlayerName($player), $message);
				$message=str_replace("{target}", $this->searchedtarget, $message);
				$message=str_replace("{reason}", $reason, $message);
				foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.notifications")){
						$online->sendMessage($message);
					}
				}
				break;
			}
		});
		$form->setTitle("§l§cAction for §r".$this->searchedtarget);
		$form->addButton("Excessive Toxicity", -1, "", "excessivetoxicity");
		$form->addButton("Impersonation", -1, "", "impersonation");
		$form->addButton("Advertisement", -1, "", "advertisement");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function existingPermanentBansForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			if($data===null){
				return;
			}
			switch($data){
				case "exit":
				$this->permanentBanHomeForm($player);
				break;
				case 0:
				$this->targetPlayer[Utils::getPlayerName($player)]=$data;
				$this->permanentBanInfoForm($player);
				break;
			}
		});
		$query=Core::getInstance()->staff->query("SELECT * FROM permanentbans;");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		$form->setTitle("Existing Permanent Bans");
		$query=Core::getInstance()->staff->query("SELECT * FROM permanentbans;");
		$i=-1;
		while($result=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$target=$result['player'];
			$form->addButton($target, -1, "", $target);
			$i=$i + 1;
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function permanentBanInfoForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
		if($data===null){
			return;
			}
			switch($data){
				case "exit":
				$this->existingPermanentBansForm($player);
				break;
				case "unban":
					$target=$this->targetPlayer[Utils::getPlayerName($player)];
					$query=Core::getInstance()->staff->query("SELECT * FROM permanentbans WHERE player='$target';");
					$result=$query->fetchArray(SQLITE3_ASSOC);
					if(!empty($result)){
						Core::getInstance()->staff->query("DELETE FROM permanentbans WHERE player='$target';");
						$message=$this->sendStaffNoti("permanentunban");
						$message=str_replace("{name}", Utils::getPlayerName($player), $message);
						$message=str_replace("{target}", $target, $message);
						foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
							if($online->hasPermission("pc.staff.notifications")){
								$online->sendMessage($message);
							}
						}
						$player->sendMessage("§aYou unbanned ".$target.".");
					}
					unset($this->targetPlayer[Utils::getPlayerName($player)]);
					break;
			}
		});
		$target=$this->targetPlayer[Utils::getPlayerName($player)];
		$query=Core::getInstance()->staff->query("SELECT * FROM permanentbans WHERE player='$target';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		if(!empty($result)){
			$reason=$result['reason'];
			$staff=$result['staff'];
			$date=$result['date'];
		}
		$form->setTitle($target);
		$form->setContent("§7Reason: §f".$reason."\n§7Date: §f".$date."\n\n§7Action by ".$staff);
		$form->addButton("Lift Punishment", -1, "", "unban");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function existingTemporaryBansForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			if($data===null){
				return;
			}
			switch($data){
				case "exit":
				$this->temporaryBanHomeForm($player);
				break;
				case 0:
				$this->targetPlayer[Utils::getPlayerName($player)]=$data;
				$this->temporaryBanInfoForm($player);
				break;
			}
		});
		$query=Core::getInstance()->staff->query("SELECT * FROM temporarybans;");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		$form->setTitle("Existing Temporary Bans");
		$query=Core::getInstance()->staff->query("SELECT * FROM temporarybans;");
		$i=-1;
		while($result=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$target=$result['player'];
			$form->addButton($target, -1, "", $target);
			$i=$i + 1;
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function temporaryBanInfoForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
		if($data===null){
			return;
			}
			switch($data){
				case "exit":
				$this->existingTemporaryBansForm($player);
				break;
				case "unban":
					$target=$this->targetPlayer[Utils::getPlayerName($player)];
					$query=Core::getInstance()->staff->query("SELECT * FROM temporarybans WHERE player='$target';");
					$result=$query->fetchArray(SQLITE3_ASSOC);
					if(!empty($result)){
						Core::getInstance()->staff->query("DELETE FROM temporarybans WHERE player='$target';");
						$message=$this->sendStaffNoti("temporaryunban");
						$message=str_replace("{name}", Utils::getPlayerName($player), $message);
						$message=str_replace("{target}", $target, $message);
						foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
							if($online->hasPermission("pc.staff.notifications")){
								$online->sendMessage($message);
							}
						}
						$player->sendMessage("§aYou unbanned ".$target.".");
					}
					unset($this->targetPlayer[Utils::getPlayerName($player)]);
					break;
			}
		});
		$target=$this->targetPlayer[Utils::getPlayerName($player)];
		$query=Core::getInstance()->staff->query("SELECT * FROM temporarybans WHERE player='$target';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		if(!empty($result)){
			$reason=$result['reason'];
			$duration=$result['duration'];
			$staff=$result['staff'];
			$givenpoints=$result['givenpoints'];
			$date=$result['date'];
			$now=time();
			$remainingTime=$duration - $now;
			$day=floor($remainingTime / 86400);
			$hourSeconds=$remainingTime % 86400;
			$hour=floor($hourSeconds / 3600);
			$minuteSec=$hourSeconds % 3600;
			$minute=floor($minuteSec / 60);
			$remainingSec=$minuteSec % 60;
			$second=ceil($remainingSec);
		}
		$form->setTitle($target);
		$form->setContent("§7Day(s): §f".$day."\n§7Hour(s): §f".$hour."\n§7Minute(s): §f".$minute."\n§7Second(s): §f".$second."\n§7Reason: §f".$reason."\n§7Points: §f".$givenpoints."\n§7Date: §f".$date."\n\n§7Action by ".$staff);
		$form->addButton("Lift Punishment", -1, "", "unban");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function existingMutesForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			if($data===null){
				return;
			}
			switch($data){
				case "exit":
				$this->muteHomeForm($player);
				break;
				case 0:
				$this->targetPlayer[Utils::getPlayerName($player)]=$data;
				$this->muteInfoForm($player);
				break;
			}
		});
		$query=Core::getInstance()->staff->query("SELECT * FROM mutes;");
		$result=$query->fetchArray(SQLITE3_ASSOC);	
		$form->setTitle("Existing Mutes");
		$query=Core::getInstance()->staff->query("SELECT * FROM mutes;");
		$i=-1;
		while($result=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$target=$result['player'];
			$form->addButton($target, -1, "", $target);
			$i=$i + 1;
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function muteInfoForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
		if($data===null){
			return;
			}
			switch($data){
				case "exit":
				$this->existingMutesForm($player);
				break;
				case "unmute":
					$target=$this->targetPlayer[Utils::getPlayerName($player)];
					$query=Core::getInstance()->staff->query("SELECT * FROM mutes WHERE player='$target';");
					$result=$query->fetchArray(SQLITE3_ASSOC);
					if(!empty($result)){
						Core::getInstance()->staff->query("DELETE FROM mutes WHERE player='$target';");
						$message=$this->sendStaffNoti("unmute");
						$message=str_replace("{name}", Utils::getPlayerName($player), $message);
						$message=str_replace("{target}", $target, $message);
						foreach(Core::getInstance()->getServer()->getOnlinePlayers() as $online){
							if($online->hasPermission("pc.staff.notifications")){
								$online->sendMessage($message);
							}
						}
						$onlinetarget=Core::getInstance()->getServer()->getPlayerExact($target);
						if($onlinetarget!==null){
							$onlinetarget->sendMessage("§aYou have been unmuted.");
						}
						$player->sendMessage("§aYou unmuted ".$target.".");
					}
					unset($this->targetPlayer[Utils::getPlayerName($player)]);
					break;
			}
		});
		$target=$this->targetPlayer[Utils::getPlayerName($player)];
		$query=Core::getInstance()->staff->query("SELECT * FROM mutes WHERE player='$target';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		if(!empty($result)){
			$reason=$result['reason'];
			$duration=$result['duration'];
			$staff=$result['staff'];
			$date=$result['date'];
			$now=time();
			$remainingTime=$duration - $now;
			$day=floor($remainingTime / 86400);
			$hourSeconds=$remainingTime % 86400;
			$hour=floor($hourSeconds / 3600);
			$minuteSec=$hourSeconds % 3600;
			$minute=floor($minuteSec / 60);
			$remainingSec=$minuteSec % 60;
			$second=ceil($remainingSec);
		}
		$form->setTitle($target);
		$form->setContent("§7Day(s): §f".$day."\n§7Hour(s): §f".$hour."\n§7Minute(s): §f".$minute."\n§7Second(s): §f".$second."\n§7Reason: §f".$reason."\n§7Date: §f".$date."\n\n§7Action by ".$staff);
		$form->addButton("Lift Punishment", -1, "", "unmute");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
}