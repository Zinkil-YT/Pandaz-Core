<?php

namespace Zinkil\pc\commands;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use Zinkil\pc\forms\{SimpleForm, CustomForm};
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class RankCommand extends PluginCommand{
	
	private $plugin;
	
	public $targetPlayer=[];
	
	public function __construct(Core $plugin){
		parent::__construct("rank", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bGive a player a rank");
		$this->setPermission("pc.command.rank");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player->hasPermission("pc.command.rank")){
			$player->sendMessage("§cYou cannot execute this command.");
		}else{
			if($player instanceof Player){
				$this->rankMainForm($player);
				return;
			}
		}
	}
	public function rankMainForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "list":
				$this->rankListForm($player);
				break;
				case "set":
				$this->playerListForm($player);
				break;
			}
		});
		$rank=$this->plugin->getDatabaseHandler()->getRank($player->getName());
		$form->setTitle("§l§cRank");
		$form->addButton("Available Ranks", -1, "", "list");
		$form->addButton("Set a Rank", -1, "", "set");
		$player->sendForm($form);
	}
	public function rankListForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "player":
				$rank="Player";
				$this->rankInfoForm($player, $rank);
				break;
				case "voter":
				$rank="Voter";
				$this->rankInfoForm($player, $rank);
				break;
				case "elite":
				$rank="Elite";
				$this->rankInfoForm($player, $rank);
				break;
				case "premium":
				$rank="Premium";
				$this->rankInfoForm($player, $rank);
				break;
				case "booster":
				$rank="Booster";
				$this->rankInfoForm($player, $rank);
				break;
				case "youtube":
				$rank="YouTube";
				$this->rankInfoForm($player, $rank);
				break;
				case "famous":
				$rank="Famous";
				$this->rankInfoForm($player, $rank);
				break;
				case "trainee":
				$rank="Trainee";
				$this->rankInfoForm($player, $rank);
				break;
				case "helper":
				$rank="Helper";
				$this->rankInfoForm($player, $rank);
				break;
				case "builder":
				$rank="Builder";
				$this->rankInfoForm($player, $rank);
				break;
				case "mod":
				$rank="Mod";
				$this->rankInfoForm($player, $rank);
				break;
				case "headmod":
				$rank="HeadMod";
				$this->rankInfoForm($player, $rank);
				break;
				case "admin":
				$rank="Admin";
				$this->rankInfoForm($player, $rank);
				break;
				case "manager":
				$rank="Manager";
				$this->rankInfoForm($player, $rank);
				break;
				case "owner":
				$rank="Owner";
				$this->rankInfoForm($player, $rank);
				break;
				case "exit":
				$this->rankMainForm($player);
				break;
			}
		});
		$form->setTitle("§l§cAvailable Ranks");
		//$form->addButton("Player\n(".$this->plugin->getDatabaseHandler()->countWithRank("Player")." total)", -1, "", "player");
		$form->addButton("Voter\n(".$this->plugin->getDatabaseHandler()->countWithRank("Voter")." total)", -1, "", "voter");
		$form->addButton("Elite\n(".$this->plugin->getDatabaseHandler()->countWithRank("Elite")." total)", -1, "", "elite");
		$form->addButton("Premium\n(".$this->plugin->getDatabaseHandler()->countWithRank("Premium")." total)", -1, "", "premium");
		$form->addButton("Booster\n(".$this->plugin->getDatabaseHandler()->countWithRank("Booster")." total)", -1, "", "booster");
		$form->addButton("YouTube\n(".$this->plugin->getDatabaseHandler()->countWithRank("YouTube")." total)", -1, "", "youtube");
		$form->addButton("Famous\n(".$this->plugin->getDatabaseHandler()->countWithRank("Famous")." total)", -1, "", "famous");
		$form->addButton("Trainee\n(".$this->plugin->getDatabaseHandler()->countWithRank("Trainee")." total)", -1, "", "trainee");
		$form->addButton("Helper\n(".$this->plugin->getDatabaseHandler()->countWithRank("Helper")." total)", -1, "", "helper");
		$form->addButton("Builder\n(".$this->plugin->getDatabaseHandler()->countWithRank("Builder")." total)", -1, "", "builder");
		$form->addButton("Mod\n(".$this->plugin->getDatabaseHandler()->countWithRank("Mod")." total)", -1, "", "mod");
		$form->addButton("HeadMod\n(".$this->plugin->getDatabaseHandler()->countWithRank("HeadMod")." total)", -1, "", "headmod");
		$form->addButton("Admin\n(".$this->plugin->getDatabaseHandler()->countWithRank("Admin")." total)", -1, "", "admin");
		$form->addButton("Manager\n(".$this->plugin->getDatabaseHandler()->countWithRank("Manager")." total)", -1, "", "manager");
		$form->addButton("Owner\n(".$this->plugin->getDatabaseHandler()->countWithRank("Owner")." total)", -1, "", "owner");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function playerListForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			if($data===null){
				return;
			}
			switch($data){
				case "exit":
				$this->rankMainForm($player);
				break;
				case 0:
				$this->targetPlayer[$player->getName()]=$data;
				$target=$this->targetPlayer[$player->getName()];
				$rank1=$this->plugin->getDatabaseHandler()->getRank($target);
				$rank2=$this->plugin->getDatabaseHandler()->getRank($player->getName());
				if($target==$player->getName() and !$player->isOp()){
					$player->sendMessage("§cYou cannot manage your own rank.");
					return;
				}
				if($rank1=="Owner" or $rank1=="Manager"){
					if($target!=$player->getName() and !$player->isOp()){
						$player->sendMessage("§cYou cannot manage a player with this rank.");
						return;
					}
				}
				$this->rankForm($player);
				break;
			}
		});
		$form->setTitle("§l§cSelect a Player");
		foreach($this->plugin->getServer()->getOnlinePlayers() as $players){
			$form->addButton($players->getName(), -1, "", $players->getName());
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function rankForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->playerListForm($player);
				break;
				case "perm":
				$this->rankSetPermForm($player);
				break;
				case "temp":
				$this->rankSetTempForm($player);
				break;
			}
		});
		$form->setTitle("§l§cSelect a Rank Variation");
		$form->addButton("Permanent", -1, "", "perm");
		$form->addButton("Temporary", -1, "", "temp");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function rankSetPermForm(Player $player):void{
		$form=new CustomForm(function(Player $player, array $data=null):void{
			switch($data){
				case 0:
				return;
				break;
			}
			$target=$this->targetPlayer[$player->getName()];
			switch($data[1]){
				case 0:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Player";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 1:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Voter";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 2:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Elite";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 3:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Premium";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 4:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Booster";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 5:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="YouTube";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 6:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Famous";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 7:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Builder";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 8:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Trainee";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 9:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Helper";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
				$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 10:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Mod";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 11:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="HeadMod";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 12:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Admin";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 13:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Manager";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case 14:
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Owner";
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					$this->plugin->getDatabaseHandler()->setRank($target, $newrank);
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank.".");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank.".");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("rankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
			}
			unset($this->targetPlayer[$player->getName()]);
		});
		$target=$this->targetPlayer[$player->getName()];
		$ranks=["Player", "Voter", "Elite", "Premium", "Booster", "YouTube", "Famous", "Builder", "Trainee", "Helper", "Mod", "HeadMod", "Admin", "Manager", "Owner"];
		$rank=$this->plugin->getDatabaseHandler()->getRank($target);
		$form->setTitle("§l§c".$target);
		$form->addLabel("§7This player is currently a/an ".$rank.".");//DATA[0]
		switch($rank){
			case "Player":
			$form->addDropdown("Choose a rank", $ranks, 0);//DATA[1]
			break;
			case "Voter":
			$form->addDropdown("Choose a rank", $ranks, 1);//DATA[1]
			break;
			case "Elite":
			$form->addDropdown("Choose a rank", $ranks, 2);//DATA[1]
			break;
			case "Premium":
			$form->addDropdown("Choose a rank", $ranks, 3);//DATA[1]
			break;
			case "Booster":
			$form->addDropdown("Choose a rank", $ranks, 4);//DATA[1]
			break;
			case "YouTube":
			$form->addDropdown("Choose a rank", $ranks, 5);//DATA[1]
			break;
			case "Famous":
			$form->addDropdown("Choose a rank", $ranks, 6);//DATA[1]
			break;
			case "Builder":
			$form->addDropdown("Choose a rank", $ranks, 7);//DATA[1]
			break;
			case "Trainee":
			$form->addDropdown("Choose a rank", $ranks, 8);//DATA[1]
			break;
			case "Helper":
			$form->addDropdown("Choose a rank", $ranks, 9);//DATA[1]
			break;
			case "Mod":
			$form->addDropdown("Choose a rank", $ranks, 10);//DATA[1]
			break;
			case "HeadMod":
			$form->addDropdown("Choose a rank", $ranks, 11);//DATA[1]
			break;
			case "Admin":
			$form->addDropdown("Choose a rank", $ranks, 12);//DATA[1]
			break;
			case "Manager":
			$form->addDropdown("Choose a rank", $ranks, 13);//DATA[1]
			break;
			case "Owner":
			$form->addDropdown("Choose a rank", $ranks, 14);//DATA[1]
			break;
			default:
			$form->addDropdown("Choose a rank", $ranks, 0);//DATA[1]
			break;
		}
		$player->sendForm($form);
	}
	public function rankSetTempForm(Player $player):void{
		$form=new SimpleForm(function(Player $player, $data=null):void{
			switch($data){
				case "exit":
				$this->playerListForm($player);
				break;
				case "elite3d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Elite";
				$days=3;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "elite3d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "elite7d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Elite";
				$days=7;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "elite7d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "elite14d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Elite";
				$days=14;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "elite14d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "elite30d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Elite";
				$days=30;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "elite30d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "premium3d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Premium";
				$days=3;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "premium3d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "premium7d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Premium";
				$days=7;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "premium7d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "premium14d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Premium";
				$days=14;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "premium14d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
				case "premium30d":
				$target=$this->targetPlayer[$player->getName()];
				$targetpl=$this->plugin->getServer()->getPlayerExact($target);
				$rank=$this->plugin->getDatabaseHandler()->getRank($target);
				$newrank="Premium";
				$days=30;
				if($rank==$newrank){
					$player->sendMessage("§cThis player has already been assigned ".$newrank.".");
				}else{
					Utils::giveTemporaryRank($target, "premium30d");
					if($targetpl instanceof Player) $targetpl->sendMessage("§aYour rank was updated to ".$newrank." for ".$days." days.");
					$player->sendMessage("§aYou updated ".$target."'s rank to ".$newrank." for ".$days." days.");
					$message=$this->plugin->getStaffUtils()->sendStaffNoti("temprankchange");
					$message=str_replace("{name}", $player->getName(), $message);
					$message=str_replace("{target}", $target, $message);
					$message=str_replace("{oldrank}", $rank, $message);
					$message=str_replace("{newrank}", $newrank, $message);
					$message=str_replace("{days}", $days, $message);
					foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
						if($online->hasPermission("pc.staff.notifications")){
							$online->sendMessage($message);
						}
					}
				}
				break;
			}
			unset($this->targetPlayer[$player->getName()]);
		});
		$target=$this->targetPlayer[$player->getName()];
		$form->setTitle("§l§c".$target);
		$form->addButton("Elite 3D", -1, "", "elite3d");
		$form->addButton("Elite 7D", -1, "", "elite7d");
		$form->addButton("Elite 14D", -1, "", "elite14d");
		$form->addButton("Elite 30D", -1, "", "elite30d");
		$form->addButton("Premium 3D", -1, "", "premium3d");
		$form->addButton("Premium 7D", -1, "", "premium7d");
		$form->addButton("Premium 14D", -1, "", "premium14d");
		$form->addButton("Premium 30D", -1, "", "premium30d");
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
	public function rankInfoForm(Player $player, string $rank):void{
		$this->rank=$rank;
		$form=new SimpleForm(function (Player $player, $data=null):void{
			if($data===null) return;
				switch($data){
				case "exit":
				$this->rankListForm($player);
				break;
				case 0:
				$this->targetPlayer[$player->getName()]=$data;
				$target=$this->targetPlayer[$player->getName()];
				$this->rankForm($player);
				break;
			}
		});
		$form->setTitle("§l§cPlayers With ".$this->rank." Rank");
		$query=$this->plugin->main->query("SELECT * FROM rank ORDER BY rank;");
		while($result=$query->fetchArray(SQLITE3_ASSOC)){
			$target=$result['player'];
			$rank=$this->plugin->getDatabaseHandler()->getRank($target);
			if($rank==$this->rank) $form->addButton($target, -1, "", $target);
		}
		$form->addButton("« Back", -1, "", "exit");
		$player->sendForm($form);
	}
}