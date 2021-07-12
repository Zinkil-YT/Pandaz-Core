<?php

declare(strict_types=1);

namespace Zinkil\pc\listeners;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use Zinkil\pc\forms\{SimpleForm, CustomForm, ModalForm};
use Zinkil\pc\duels\groups\{DuelGroup, PartyDuelGroup};
use Zinkil\pc\duels\groups\BotDuelGroup;
use Zinkil\pc\duels\groups\QueuedPlayer;
use Zinkil\pc\duels\groups\MatchedGroup;
use Zinkil\pc\Core;
use Zinkil\pc\Kits;
use Zinkil\pc\Utils;

class ItemListener implements Listener{
	
	public $plugin;
	
	public $targetDuel=[];
	
	private $formCd=[];
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	public function onInteract(PlayerInteractEvent $event){
		$player=$event->getPlayer();
		$item=$player->getInventory()->getItemInHand();
		if($item->getCustomName()=="§l§bUnranked"){
			$event->setCancelled();
			if($player->isInParty()){
				$player->sendMessage("§bThis is disabled, you are in a party.");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->unrankedForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->unrankedForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bRanked"){
			$event->setCancelled();
			if($player->isInParty()){
				$player->sendMessage("§bThis is disabled, you are in a party.");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->rankedForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->rankedForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bDuels"){
			$event->setCancelled();
			if($player->isInParty()){
				$player->sendMessage("§bThis is disabled, you are in a party.");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->duelForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->duelForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bSpectate"){
			$event->setCancelled();
			if($player->isInParty()){
				$player->sendMessage("§bThis is disabled, you are in a party.");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->spectateForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->spectateForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bBot Duels"){
			$event->setCancelled();
			if($player->isInParty()){
				$player->sendMessage("§bThis is disabled, you are in a party.");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->botDuelForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->botDuelForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bFFA"){
			$event->setCancelled();
			if($player->isInParty()){
					$player->sendMessage("§bThis is disabled, you are in a party.");
					return;
			}
			if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
				$player->sendMessage("§bYou cannot view this while queued.");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->warpForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->warpForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bDaily Rankings"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->dailyRankingsForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->dailyRankingsForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bToys"){
			$event->setCancelled();
			if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
				$player->sendMessage("§bYou cannot view this while queued.");
				return;
			}
			if($player->isOp() or $this->plugin->getDatabaseHandler()->voteAccessExists($player)){
			}else{
				$player->sendMessage("§bOnly voters have access to toys, you can vote at ".$this->plugin->getVote().".");
				return;
			}
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->toysForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->toysForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bParty"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->plugin->getForms()->partyForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->plugin->getForms()->partyForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bCosmetics"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->cosmeticsForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->cosmeticsForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bPlayer Portal"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->playerPortalForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->playerPortalForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bMarket"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->marketForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->marketForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bLobby"){
			$event->setCancelled();
			$player->sendTo(0, true);
			return;
		}
		if($item->getCustomName()=="§l§bLeave Duel"){
			$event->setCancelled();
			$duel=$this->plugin->getDuelHandler()->getDuelFromSpec($player);
			$pduel=$this->plugin->getDuelHandler()->getPartyDuelFromSpec($player);
			if(!is_null($duel)) $duel->removeSpectator($player, true);
			if(!is_null($pduel)) $pduel->removeSpectator($player, true);
			return;
		}
		if($item->getCustomName()=="§l§bTeleport"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->plugin->getStaffUtils()->teleportForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->plugin->getStaffUtils()->teleportForm($player);
					return;
				}
			}
		}
		if($item->getCustomName()=="§l§bStaff Portal"){
			$event->setCancelled();
			$cooldown=1;
			if(!isset($this->formCd[$player->getName()])){
				$this->formCd[$player->getName()]=time();
				$this->plugin->getStaffUtils()->staffPortalForm($player);
			}else{
				if($cooldown > time() - $this->formCd[$player->getName()]){
					$time=time() - $this->formCd[$player->getName()];
				}else{
					$this->formCd[$player->getName()]=time();
					$this->plugin->getStaffUtils()->staffPortalForm($player);
					return;
				}
			}
		}
	}
	public function profileTap2(BlockPlaceEvent $event){
		$block=$event->getBlock();
		$id=$block->getId();
		$meta=$block->getDamage();
		if($id==144 and $meta==3){
			if($block->getCustomName()=="§l§bProfile"){
				$event->setCancelled();
				}else{
					return;
			}
		}
	}
	public function cosmeticsForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0:
				$color="default";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 1:
				$color="pink";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 2:
				$color="purple";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 3:
				$color="blue";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 4:
				$color="cyan";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 5:
				$color="green";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 6:
				$color="yellow";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 7:
				$color="orange";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 8:
				$color="white";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 9:
				$color="grey";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 10:
				$color="black";
				if(Utils::potSplashColor($player)!=$color){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "pot-splash-color", $color);
						$player->sendMessage("§aYour pot splash color is now ".$color.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
			}
			switch($data[1]){
				case 0:
				$multiplier="off";
				if(Utils::particleMod($player)!=$multiplier){
					Utils::setPlayerData($player, "particle-mod", $multiplier);
					$player->sendMessage("§aYour particle mod multiplier is now set to ".$multiplier.".");
				}
				break;
				case 1:
				$multiplier="x1";
				if(Utils::particleMod($player)!=$multiplier){
					Utils::setPlayerData($player, "particle-mod", $multiplier);
					$player->sendMessage("§aYour particle mod multiplier is now set to ".$multiplier.".");
				}
				break;
				case 2:
				$multiplier="x2";
				if(Utils::particleMod($player)!=$multiplier){
					Utils::setPlayerData($player, "particle-mod", $multiplier);
					$player->sendMessage("§aYour particle mod multiplier is now set to ".$multiplier.".");
				}
				break;
				case 3:
				$multiplier="x4";
				if(Utils::particleMod($player)!=$multiplier){
					Utils::setPlayerData($player, "particle-mod", $multiplier);
					$player->sendMessage("§aYour particle mod multiplier is now set to ".$multiplier.".");
				}
				break;
				case 4:
				$multiplier="x8";
				if(Utils::particleMod($player)!=$multiplier){
					Utils::setPlayerData($player, "particle-mod", $multiplier);
					$player->sendMessage("§aYour particle mod multiplier is now set to ".$multiplier.".");
				}
				break;
			}
			switch($data[2]){
				case 0:
				$pot="default";
				if(Utils::preferredPot($player)!=$pot){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "preferred-pot", $pot);
						$player->sendMessage("§aYour preferred pot is now set to ".$pot.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
				case 1:
				$pot="fast";
				if(Utils::preferredPot($player)!=$pot){
					if($player->isOp() or $player->isElite() or $player->isPremium() or $player->isStaff()){
						Utils::setPlayerData($player, "preferred-pot", $pot);
						$player->sendMessage("§aYour preferred  pot is now set to ".$pot.".");
					}else{
						$player->sendMessage("§bYou do not have access to this cosmetic.");
					}
				}
				break;
			}
		});
		$colors=["§cDefault","§dPink","§5Purple","§1Blue","§bCyan","§aGreen","§eYellow","§6Orange","§fWhite","§7Grey","§0Black"];
		$multipliers=["Off","x1","x2","x4","x8"];
		$pots=["Default", "Fast"];
		$preferredpot=Utils::preferredPot($player);
		$form->setTitle("§l§cCosmetics");
		$def1=-1;
		if(Utils::potSplashColor($player)=="default") $def1=0;
		if(Utils::potSplashColor($player)=="pink") $def1=1;
		if(Utils::potSplashColor($player)=="purple") $def1=2;
		if(Utils::potSplashColor($player)=="blue") $def1=3;
		if(Utils::potSplashColor($player)=="cyan") $def1=4;
		if(Utils::potSplashColor($player)=="green") $def1=5;
		if(Utils::potSplashColor($player)=="yellow") $def1=6;
		if(Utils::potSplashColor($player)=="orange") $def1=7;
		if(Utils::potSplashColor($player)=="white") $def1=8;
		if(Utils::potSplashColor($player)=="grey") $def1=9;
		if(Utils::potSplashColor($player)=="black") $def1=10;
		$form->addStepSlider("Pot Splash Color", $colors, $def1, null);//data0
		$def2=-1;
		if(Utils::particleMod($player)=="off") $def2=0;
		if(Utils::particleMod($player)=="x1") $def2=1;
		if(Utils::particleMod($player)=="x2") $def2=2;
		if(Utils::particleMod($player)=="x4") $def2=3;
		if(Utils::particleMod($player)=="x8") $def2=4;
		$form->addStepSlider("Extra Critical Particles", $multipliers, $def2, null);//data0
		switch($preferredpot){
			case "default":
			$form->addDropdown("Pick your preferred potion", $pots, 0);
			break;
			case "fast":
			$form->addDropdown("Pick your preferred potion", $pots, 1);
			break;
			default:
			$form->addDropdown("Pick your preferred potion", $pots, 0);
			break;
		}
		$player->sendForm($form);
	}
	public function playerPortalForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "stats":
				$this->statsForm($player);
				break;
				case "friends":
				$this->friendsForm($player);
				break;
				case "locker":
				$this->lockerForm($player);
				break;
				case "settings":
				$this->settingsForm($player);
				break;
				case "size":
				$this->sizeForm($player);
				break;
				default:
				return;
			}
		});
		$form->setTitle("§l§cPlayer Portal");
		$form->addButton("Stats", -1, "", "stats");
		$form->addButton("Settings", -1, "", "settings");
		$form->addButton("Size", -1, "", "size");
		$player->sendForm($form);
	}
	public function statsForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->playerPortalForm($player);
				break;
			}
		});
		$elo=$this->plugin->getDatabaseHandler()->getRankedElo($player->getName());
		$wins=$this->plugin->getDatabaseHandler()->getWins($player->getName());
		$losses=$this->plugin->getDatabaseHandler()->getLosses($player->getName());
		$ffaelo=$this->plugin->getDatabaseHandler()->getElo($player->getName());
		$kills=$this->plugin->getDatabaseHandler()->getKills($player->getName());
		$deaths=$this->plugin->getDatabaseHandler()->getDeaths($player->getName());
		$kdr=$this->plugin->getDatabaseHandler()->getKdr($player->getName());
		$killstreak=$this->plugin->getDatabaseHandler()->getKillstreak($player->getName());
		$bestkillstreak=$this->plugin->getDatabaseHandler()->getBestKillstreak($player->getName());
		$form->setTitle("§l§cStats");
		$form->setContent("§bCompetitive Stats\n§fElo: ".$elo."\nWins: ".$wins."\nLosses: ".$losses."\n\n§bCasual Stats\n§fKills: ".$kills."\nDeaths: ".$deaths."\nKDR: ".$kdr."\nKillstreak: ".$killstreak." §7(".$bestkillstreak.")");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function friendsForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->playerPortalForm($player);
				break;
				case "friends":
				$this->viewFriendsForm($player);
				break;
			}
		});
		$friends=$this->plugin->getDatabaseHandler()->getFriendsCount($player->getName());
		$form->setTitle("Friends");
		$form->addButton("Friends [".$friends."]", -1, "", "friends");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function lockerForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "capes":
				$this->capesForm($player);
				break;
				case "exit":
				$this->playerPortalForm($player);
				break;
			}
		});
		$form->setTitle("Locker");
		$form->addButton("Capes", -1, "", "capes");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function capesForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "Pandaz":
				if($player->hasCape()){
					$oldSkin=$player->getSkin();
					$skin=new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), "", $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
					$player->setSkin($skin);
					$player->sendSkin();
					$player->setHasCape(false);
				}else{
					$cape=Utils::createImage("Pandaz");
					$skin=new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $cape, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
					$player->setSkin($skin);
					$player->sendSkin();
					$player->setHasCape(true);
				}
				break;
				case "exit":
				$this->playerPortalForm($player);
				break;
			}
		});
		$form->setTitle("Locker");
		$form->addButton("Pandaz", -1, "", "Pandaz");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function settingsForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "pot":
				$this->potFeedbackForm($player);
				break;
				case "leaderboards":
				$this->showInLdbrdsForm($player);
				break;
				case "scoreboard":
				$this->scoreboardForm($player);
				break;
				case "requeue":
				$this->requeueForm($player);
				break;
				case "rekit":
				$this->rekitForm($player);
				break;
				case "sprint":
				$this->sprintForm($player);
				break;
				case "cps":
				$this->cpsForm($player);
				break;
				case "combat":
				$this->combatForm($player);
				break;
				case "swingsounds":
				$this->swingSoundsForm($player);
				break;
				case "exit":
				$this->playerPortalForm($player);
				break;
			}
		});
		$form->setTitle("§l§cSettings");
		$form->addButton("Show In Leaderboards\n§bYour visibility in leaderboards", -1, "", "leaderboards");
		$form->addButton("Display Scoreboard\n§bYour scoreboard's visibility", -1, "", "scoreboard");
		$form->addButton("Auto Re-queue\n§bAuto re-queue after a win", -1, "", "requeue");
		$form->addButton("Auto Re-kit\n§bAuto re-kit after a kill", -1, "", "rekit");
		$form->addButton("Auto Sprint\n§bSprint automatically", -1, "", "sprint");
		$form->addButton("CPS Counter\n§bDisplays your CPS", -1, "", "cps");
		$form->addButton("Combat Counter\n§bDisplays Combat Duration", -1, "", "combat");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function potFeedbackForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isPotFeedbackEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "pot-feedback", false);
				$player->sendMessage("§aYou will no longer receive feedback on your pots.");
				break;
				case 1://on
				if(Utils::isPotFeedbackEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "pot-feedback", true);
				$player->sendMessage("§aYou will now receive feedback on your pots as they are thrown.");
				return;
				break;
			}
		});
		$form->setTitle("Pot Feedback");
		if(Utils::isPotFeedbackEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function showInLdbrdsForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isShowInLeaderboardsEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "show-in-leaderboards", false);
				$player->sendMessage("§aYou will no longer be shown in the leaderboards.");
				break;
				case 1://on
				if(Utils::isShowInLeaderboardsEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "show-in-leaderboards", true);
				$player->sendMessage("§aYou will now be shown in the leaderboards.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cShow In Leaderboards");
		if(Utils::isShowInLeaderboardsEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function scoreboardForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isScoreboardEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "scoreboard", false);
				$player->sendMessage("§aYou will no longer see your scoreboard.");
				$this->plugin->getScoreboardHandler()->removeScoreboard($player);
				break;
				case 1://on
				if(Utils::isScoreboardEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "scoreboard", true);
				$this->plugin->getScoreboardHandler()->sendMainScoreboard($player, "Pandaz");
				$player->sendMessage("§aYou will now see your scoreboard.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cDisplay Scoreboard");
		if(Utils::isScoreboardEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function requeueForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isAutoRequeueEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "auto-requeue", false);
				$player->sendMessage("§aYou will no longer be automatically queued after a win.");
				break;
				case 1://on
				if(Utils::isAutoRequeueEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "auto-requeue", true);
				$player->sendMessage("§aYou will now be automatically queued after a win.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cAuto Re-queue");
		if(Utils::isAutoRequeueEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function rekitForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isAutoRekitEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "auto-rekit", false);
				$player->sendMessage("§aYou will no longer automatically receive your corresponding kit after a kill.");
				break;
				case 1://on
				if(Utils::isAutoRekitEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "auto-rekit", true);
				$player->sendMessage("§aYou will now automatically receive your corresponding kit after a kill.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cAuto Re-kit");
		if(Utils::isAutoRekitEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function sprintForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isAutoSprintEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "auto-sprint", false);
				$player->sendMessage("§aYou will no longer automatically sprint.");
				break;
				case 1://on
				if(Utils::isAutoSprintEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "auto-sprint", true);
				$player->sendMessage("§aYou will now automatically sprint.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cAuto Sprint");
		if(Utils::isAutoSprintEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function cpsForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isCpsCounterEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "cps-counter", false);
				$player->sendMessage("§aYou will no longer see your CPS counter.");
				break;
				case 1://on
				if(Utils::isCpsCounterEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "cps-counter", true);
				$player->sendMessage("§aYou will now see your CPS counter.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cCPS Counter");
		if(Utils::isCpsCounterEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function combatForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isCombatCounterEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "combat-counter", false);
				$player->sendMessage("§aYou will no longer see the Combat counter.");
				break;
				case 1://on
				if(Utils::isCombatCounterEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "combat-counter", true);
				$player->sendMessage("§aYou will now see the Combat counter.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cCombat Counter");
		if(Utils::isCombatCounterEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function swingSoundsForm(Player $player):void{
		$form=new CustomForm(function(Player $player, $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			switch($data[0]){
				case 0://off
				if(Utils::isSwingSoundEnabled($player)==false){
					return;
				}
				Utils::setPlayerData($player, "swing-sounds", false);
				$player->sendMessage("§aYou will no longer hear swing or hit sounds.");
				break;
				case 1://on
				if(Utils::isSwingSoundEnabled($player)==true){
					return;
				}
				Utils::setPlayerData($player, "swing-sounds", true);
				$player->sendMessage("§aYou will now hear swing and hit sounds.");
				return;
				break;
			}
		});
		$form->setTitle("§l§cSwing & Hit Sounds");
		if(Utils::isSwingSoundEnabled($player)==true){
			$form->addToggle("Enabled", true, null);//data[0]
		}else{
			$form->addToggle("Disabled", false, null);//data[0]
		}
		$player->sendForm($form);
	}
	public function dailyRankingsForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "kills":
				$this->dailyKillsForm($player);
				break;
				case "deaths":
				$this->dailyDeathsForm($player);
				break;
			}
		});
		$form->setTitle("§l§cDaily Rankings");
		$form->addButton("Kills", -1, "", "kills");
		$form->addButton("Deaths", -1, "", "deaths");
		$player->sendForm($form);
	}
	public function dailyKillsForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->dailyRankingsForm($player);
				break;
			}
		});
		$form->setTitle("§l§cDaily Kills");
		$query=$this->plugin->main->query("SELECT * FROM temporary ORDER BY dailykills DESC LIMIT 20;");
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$players=$resultArr['player'];
			$val=$this->plugin->getDatabaseHandler()->getDailyKills($players);
			if(Utils::isShowInLeaderboardsEnabled($players)==true){
				$form->addButton($players."\n§b".$val);
			}
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function dailyDeathsForm(Player $player):void{
		$form=new SimpleForm(function (Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->dailyRankingsForm($player);
				break;
			}
		});
		$form->setTitle("§l§cDaily Deaths");
		$query=$this->plugin->main->query("SELECT * FROM temporary ORDER BY dailydeaths DESC LIMIT 20;");
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$players=$resultArr['player'];
			$val=$this->plugin->getDatabaseHandler()->getDailyDeaths($players);
			if(Utils::isShowInLeaderboardsEnabled($players)==true){
				$form->addButton($players."\n§b".$val);
			}
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function toysForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "size":
				$this->sizeForm($player);
				break;
				default:
				return;
			}
		});
		$form->setTitle("Toys");
		$form->addButton("Size\n§bBecome a midget or a giant", -1, "", "size");
		$player->sendForm($form);
	}
	public function sizeForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "small":
				$player->setScale(0.5);
				break;
				case "normal":
				$player->setScale(1);
				break;
				case "medium":
				$player->setScale(1.5);
				break;
				case "large":
				$player->setScale(2);
				break;
			}
		});
		$form->setTitle("§l§cSize");
		$form->addButton("Small", -1, "", "small");
		$form->addButton("Normal", -1, "", "normal");
		$form->addButton("Medium", -1, "", "medium");
		$form->addButton("Large", -1, "", "large");
		$player->sendForm($form);
	}
	public function warpForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "nodebuff":
				$player->sendTo(1, true, true);
				break;
				case "gapple":
				$player->sendTo(2, true, true);
				break;
				case "opgapple":
				$player->sendTo(3, true, true);
				break;
				case "combo":
				$player->sendTo(4, true, true);
				break;
				case "fist":
				$player->sendTo(5, true, true);
				break;
				case "resistance":
				$player->sendTo(9, true, true);
				break;
				case "sumoffa":
				$player->sendTo(11, true, true);
				break;
				case "knockbackffa":
				$player->sendTo(12, true, true);
				break;
				case "offline":
				$player->sendMessage("§bThis arena is currently offline.");
				break;
				case "wip":
				$player->sendMessage("§bThis arena is currently being fixed.");
				break;
			}
		});
		$nodebuff=$this->plugin->getServer()->getLevelByName("nodebuff");
		$nodebufflow=$this->plugin->getServer()->getLevelByName("nodebuff-low");
		$nodebuffjava=$this->plugin->getServer()->getLevelByName("nodebuff-java");
		$gapple=$this->plugin->getServer()->getLevelByName("gapple");
		$opgapple=$this->plugin->getServer()->getLevelByName("opgapple");
		$combo=$this->plugin->getServer()->getLevelByName("combo");
		$fist=$this->plugin->getServer()->getLevelByName("fist");
		$resistance=$this->plugin->getServer()->getLevelByName("resistance");
		$sumoffa=$this->plugin->getServer()->getLevelByName("sumoffa");
		$knockbackffa=$this->plugin->getServer()->getLevelByName("BuildFFA");
		if(!$this->plugin->getServer()->isLevelLoaded("nodebuff")){
			$count1="§cOffline";
			$c1="offline";
		}else{
			$count1="Playing: ".count($nodebuff->getPlayers());
			$c1="nodebuff";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("gapple")){
			$count2="§cOffline";
			$c2="offline";
		}else{
			$count2="Playing: ".count($gapple->getPlayers());
			$c2="gapple";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("combo")){
			$count4="§cOffline";
			$c4="offline";
		}else{
			$count4="Playing: ".count($combo->getPlayers());
			$c4="combo";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("fist")){
			$count5="§cOffline";
			$c5="offline";
		}else{
			$count5="Playing: ".count($fist->getPlayers());
			$c5="fist";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("resistance")){
			$count6="§cOffline";
			$c6="offline";
		}else{
			$count6="Playing: ".count($resistance->getPlayers());
			$c6="resistance";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("sumoffa")){
			$count7="§cOffline";
			$c7="offline";
		}else{
			$count7="Playing: ".count($sumoffa->getPlayers());
			$c7="sumoffa";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("BuildFFA")){
			$count8="§cOffline";
			$c8="offline";
		}else{
			$count8="Playing: ".count($knockbackffa->getPlayers());
			$c8="knockbackffa";
		}
		$form->setTitle("§l§cFFA");
		$form->addButton("Fist\n".$count5, 0, "textures/items/beef_cooked", $c5);
		$form->addButton("Resistance\n".$count6, 0, "textures/ui/resistance_effect", $c6);
		$form->addButton("Sumo\n".$count7, 0, "textures/ui/slow_falling_effect", $c7);
		$form->addButton("NoDebuff\n".$count1, 0, "textures/items/potion_bottle_splash_heal", $c1);
		$form->addButton("Gapple\n".$count2, 0, "textures/items/apple_golden", $c2);
		$form->addButton("KnockBackFFA\n".$count8, 0, "textures/items/stick", $c8);
		$form->addButton("Combo\n".$count4, 0, "textures/items/fish_pufferfish_raw", $c4);
		$player->sendForm($form);
	}
	public function nodebuffForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->warpForm($player);
				break;
				case "normal":
				$player->sendTo(1, true, true);
				break;
				case "lowkb":
				$player->sendTo(7, true, true);
				break;
				case "java":
				$player->sendTo(8, true, true);
				break;
				case "offline":
				$player->sendMessage("§bThis arena is currently offline.");
				break;
				case "wip":
				$player->sendMessage("§bThis arena is currently being fixed.");
				break;
			}
		});
		$normal=$this->plugin->getServer()->getLevelByName("nodebuff");
		$lowkb=$this->plugin->getServer()->getLevelByName("nodebuff-low");
		$java=$this->plugin->getServer()->getLevelByName("nodebuff-java");
		if(!$this->plugin->getServer()->isLevelLoaded("nodebuff")){
			$count1="§cOffline";
			$c1="offline";
		}else{
			$count1="Playing: ".count($normal->getPlayers());
			$c1="normal";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("nodebuff-low")){
			$count2="§cOffline";
			$c2="offline";
		}else{
			$count2="Playing: ".count($lowkb->getPlayers());
			$c2="lowkb";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("nodebuff-java")){
			$count3="§cOffline";
			$c3="offline";
		}else{
			$count3="Playing: ".count($java->getPlayers());
			$c3="java";
		}
		$form->setTitle("§l§cNoDebuff");
		$form->addButton("Normal\n".$count1, 0, "textures/items/potion_bottle_splash_heal", $c1);
		$form->addButton("Low KB\n".$count2, 0, "textures/items/potion_bottle_splash_heal", $c2);
		$form->addButton("Java\n".$count3, 0, "textures/items/potion_bottle_splash_heal", $c3);
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function gappleForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->warpForm($player);
				break;
				case "normal":
				$player->sendTo(2, true, true);
				break;
				case "op":
				$player->sendTo(3, true, true);
				break;
				case "offline":
				$player->sendMessage("§bThis arena is currently offline.");
				break;
				case "wip":
				$player->sendMessage("§bThis arena is currently being fixed.");
				break;
			}
		});
		$normal=$this->plugin->getServer()->getLevelByName("gapple");
		$op=$this->plugin->getServer()->getLevelByName("opgapple");
		if(!$this->plugin->getServer()->isLevelLoaded("gapple")){
			$count1="§cOffline";
			$c1="offline";
		}else{
			$count1="Playing: ".count($normal->getPlayers());
			$c1="normal";
		}
		if(!$this->plugin->getServer()->isLevelLoaded("opgapple")){
			$count2="§cOffline";
			$c2="offline";
		}else{
			$count2="Playing: ".count($op->getPlayers());
			$c2="op";
		}
		$form->setTitle("§l§cGapple");
		$form->addButton("Normal\n".$count1, 0, "textures/items/apple_golden", $c1);
		$form->addButton("OP\n".$count2, 0, "textures/items/apple_golden", $c2);
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function botDuelForm(Player $player):void{ 
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "easy":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					if($this->plugin->getDuelHandler()->isAnArenaOpen("easy")){
						$this->plugin->getDuelHandler()->createBotDuel($player, "Easy");
					}
				}else{
					$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
					if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "medium":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					if($this->plugin->getDuelHandler()->isAnArenaOpen("medium")){
						$this->plugin->getDuelHandler()->createBotDuel($player, "Medium");
					}
				}else{
					$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
					if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "hard":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					if($this->plugin->getDuelHandler()->isAnArenaOpen("hard")){
						$this->plugin->getDuelHandler()->createBotDuel($player, "Hard");
					}
				}else{
					$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
					if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "hacker":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					if($this->plugin->getDuelHandler()->isAnArenaOpen("hacker")){
						$this->plugin->getDuelHandler()->createBotDuel($player, "Hacker");
					}
				}else{
					$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
					if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
			}
		});
		$form->setTitle("§l§cBot Duels");
		$form->setContent("§fSelect your bot's difficulty.");
		$form->addButton("§aEasy §fBot", 0, "textures/items/iron_ingot", "easy");
		$form->addButton("§eMedium §fBot", 0, "textures/items/gold_ingot", "medium");
		$form->addButton("§6Hard §fBot", 0, "textures/items/diamond", "hard");
		$form->addButton("§cHacker §fBot", 0, "textures/items/emerald", "hacker");
		$player->sendForm($form);
	}
	public function duelForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "ranked":
				$this->rankedForm($player);
				break;
				case "unranked":
				$this->unrankedForm($player);
				break;
				case "duels":
				$this->spectateForm($player);
				break;
				case "leave":
				$this->plugin->getDuelHandler()->removePlayerFromQueue($player);
				break;
			}
		});
		$rankedqueued=0;
		$unrankedqueued=0;
		$queues=$this->plugin->getDuelHandler()->getQueuedPlayers();
		foreach($queues as $queue){
			if($queue->isRanked()){
				$rankedqueued++;
			}else{
				$unrankedqueued++;
			}
		}
		$rankedmatches=0;
		$unrankedmatches=0;
		$duels=$this->plugin->getDuelHandler()->getDuelsInProgress();
		foreach($duels as $duel){
			if($duel instanceof DuelGroup){
				if($duel->isRanked()){
					$rankedmatches++;
				}else{
					$unrankedmatches++;
				}
			}
		}
		$form->setTitle("§l§cDuels");
		$form->addButton("Ranked\nQueued: ".$rankedqueued." Matches: ".$rankedmatches, -1, "", "ranked");
		$form->addButton("Unranked\nQueued: ".$unrankedqueued." Matches: ".$unrankedmatches, -1, "", "unranked");
		$form->addButton("Ongoing Duels\n[ ".$this->plugin->getDuelHandler()->getNumberOfDuelsInProgress()." ]", -1, "", "duels");
		if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
			$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
			if(is_null($result)) return;
			$ranked=$result->isRanked();
			if($ranked===false){
				$ranked="Unranked";
			}
			if($ranked===true){
				$ranked="Ranked";
			}
			$queue=$result->getQueue();
			$form->setContent("§cYou are queued for ".$ranked." ".$queue.".");
			$form->addButton("§bLeave Queue", -1, "", "leave");
		}
		$player->sendForm($form);
	}
	public function rankedForm(Player $player):void{ 
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "nodebuff":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "NoDebuff", true); //true for ranked
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "gapple":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Gapple", true);
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "soup":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Soup", true);
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "builduhc":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "BuildUHC", true);
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "diamond":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Diamond", true);
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "leave":
				$this->plugin->getDuelHandler()->removePlayerFromQueue($player);
				break;
				case "exit":
				$this->duelForm($player);
				break;
			}
		});
		$nodebuffqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("NoDebuff", true);
		$nodebuffplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("NoDebuff", true);
		$gapplequeued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Gapple", true);
		$gappleplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Gapple", true);
		$soupqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Soup", true);
		$soupplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Soup", true);
		$builduhcqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("BuildUHC", true);
		$builduhcplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("BuildUHC", true);
		$diamondqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Diamond", true);
		$diamondplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Diamond", true);
		$form->setTitle("§l§cRanked");
		$form->addButton("NoDebuff\nQueued: ".$nodebuffqueued." Matches: ".$nodebuffplaying, 0, "textures/items/potion_bottle_splash_heal", "nodebuff");
		$form->addButton("Gapple\nQueued: ".$gapplequeued." Matches: ".$gappleplaying, 0, "textures/items/apple_golden", "gapple");
		$form->addButton("Soup\nQueued: ".$soupqueued." Matches: ".$soupplaying, 0, "textures/items/mushroom_stew", "soup");
		$form->addButton("BuildUHC\nQueued: ".$builduhcqueued." Matches: ".$builduhcplaying, 0, "textures/items/bucket_lava", "builduhc");
		$form->addButton("Diamond\nQueued: ".$diamondqueued." Matches: ".$diamondplaying, 0, "textures/ui/strength_effect", "diamond");
		if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
			$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
			if(is_null($result)) return;
			$ranked=$result->isRanked();
			if($ranked===false){
				$ranked="Unranked";
			}
			if($ranked===true){
				$ranked="Ranked";
			}
			$queue=$result->getQueue();
			$form->setContent("§cYou are queued for ".$ranked." ".$queue.".");
			$form->addButton("§bLeave Queue", -1, "", "leave");
		}
		$player->sendForm($form);
	}
	public function unrankedForm(Player $player):void{ 
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "nodebuff":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "NoDebuff", false); //false for unranked
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "gapple":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Gapple", false); //false for unranked
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "soup":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Soup", false);
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "builduhc":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "BuildUHC", false);
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "combo":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Combo", false); //false for unranked
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "sumo":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Sumo", false); //false for unranked
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "diamond":
				if(!$this->plugin->getDuelHandler()->isPlayerInQueue($player) and !$this->plugin->getDuelHandler()->isInDuel($player)){
					$this->plugin->getDuelHandler()->addPlayerToQueue($player, "Diamond", false); //false for unranked
					}else{
						$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
						if(!is_null($result)) $player->sendMessage("§bYou are already in a queue.");
				}
				break;
				case "leave":
				$this->plugin->getDuelHandler()->removePlayerFromQueue($player);
				break;
				case "exit":
				$this->duelForm($player);
				break;
			}
		});
		$nodebuffqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("NoDebuff", false);
		$nodebuffplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("NoDebuff", false);
		$gapplequeued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Gapple", false);
		$gappleplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Gapple", false);
		$soupqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Soup", false);
		$soupplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Soup", false);
		$builduhcqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("BuildUHC", false);
		$builduhcplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("BuildUHC", false);
		$comboqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Combo", false);
		$comboplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Combo", false);
		$sumoqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Sumo", false);
		$sumoplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Sumo", false);
		$diamondqueued=$this->plugin->getDuelHandler()->getNumberQueuedFor("Diamond", false);
		$diamondplaying=$this->plugin->getDuelHandler()->getNumberOfDuelsOfQueue("Diamond", false);
		$form->setTitle("§l§cUnranked");
		$form->addButton("NoDebuff\nQueued: ".$nodebuffqueued." Matches: ".$nodebuffplaying, 0, "textures/items/potion_bottle_splash_heal", "nodebuff");
		$form->addButton("Gapple\nQueued: ".$gapplequeued." Matches: ".$gappleplaying, 0, "textures/items/apple_golden", "gapple");
		$form->addButton("Soup\nQueued: ".$soupqueued." Matches: ".$soupplaying, 0, "textures/items/mushroom_stew", "soup");
		$form->addButton("BuildUHC\nQueued: ".$builduhcqueued." Matches: ".$builduhcplaying, 0, "textures/items/bucket_lava", "builduhc");
		$form->addButton("Diamond\nQueued: ".$diamondqueued." Matches: ".$diamondplaying, 0, "textures/ui/strength_effect", "diamond");
		$form->addButton("Combo\nQueued: ".$comboqueued." Matches: ".$comboplaying, 0, "textures/items/fish_pufferfish_raw", "combo");
		$form->addButton("Sumo\nQueued: ".$sumoqueued." Matches: ".$sumoplaying, 0, "textures/ui/slow_falling_effect", "sumo");
		if($this->plugin->getDuelHandler()->isPlayerInQueue($player)){
			$result=$this->plugin->getDuelHandler()->getQueuedPlayer($player);
			if(is_null($result)) return;
			$ranked=$result->isRanked();
			if($ranked===false){
				$ranked="Unranked";
			}
			if($ranked===true){
				$ranked="Ranked";
			}
			$queue=$result->getQueue();
			$form->setContent("§cYou are queued for ".$ranked." ".$queue.".");
			$form->addButton("§bLeave Queue", -1, "", "leave");
		}
		$player->sendForm($form);
	}
	public function spectateForm(Player $player):void{ 
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				//$this->duelForm($player);
				return;
				break;
				case 0:
				$duel=null;
				$this->targetDuel[Utils::getPlayerName($player)]=$data;
				$normal=$this->plugin->getDuelHandler()->getDuel($data);
				$party=$this->plugin->getDuelHandler()->getPartyDuel($data);
				if($normal===null){
					$duel=$party;
				}elseif($party===null){
					$duel=$normal;
				}
				if($this->plugin->getDuelHandler()->isInDuel($player) or $this->plugin->getDuelHandler()->isInPartyDuel($player)){
					$player->sendMessage("§bYou cannot spectate a duel at this moment.");
				}else{
					if($duel!==null) $duel->addSpectator($player);
				}
				break;
			}
		});
		$form->setTitle("§l§cSpectate");
		foreach($this->plugin->getDuelHandler()->getDuelsInProgress() as $duel){
			if($duel instanceof DuelGroup){
				$p=$duel->getPlayer();
				$o=$duel->getOpponent();
				$playerDS=Utils::getPlayerDisplayName($p);
				$opponentDS=Utils::getPlayerDisplayName($o);
				$queue=$duel->getQueue();
				$ranked=$duel->isRanked();
				if($ranked===true){
					$ranked="Ranked";
				}else{
					$ranked="Unranked";
				}
			}
			$form->addButton(" §b ".$playerDS." §6vs§c ".$opponentDS."\n§e".$ranked." ".$queue, -1, "", $duel->getPlayerName());
		}
		foreach($this->plugin->getDuelHandler()->getPartyDuelsInProgress() as $duel){
			if($duel instanceof PartyDuelGroup){
				$party=$duel->getParty()->getName();
				$queue=$duel->getQueue();
				$allowspecs=$duel->getAllowSpecs();
			}
			if($allowspecs===true) $form->addButton($party."'s Party\n§e".$queue, -1, "", $duel->getParty()->getLeader());
		}
		$form->addButton("Exit", -1, "", "exit");
		$player->sendForm($form);
	}
}