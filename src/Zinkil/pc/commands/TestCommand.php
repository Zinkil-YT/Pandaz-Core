<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\bots\{TestBot, EasyBot, MediumBot, HardBot, HackerBot};

class TestCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("test", $plugin);
		$this->setAliases(["t"]);
		$this->plugin=$plugin;
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if($this->plugin->getDuelHandler()->isInPartyDuel($player)){
			$duel=$this->plugin->getDuelHandler()->getPartyDuel($player);
			foreach($duel->getPlayers() as $players){
				$player->sendMessage("Duel players: ".$players);
			}
			if($duel===null){
				$player->sendMessage("Not in party duel.");
			}else{
				$player->sendMessage("In party duel.");
			}
		}
		/*if($player->isInParty()){
			foreach($player->getParty()->getMembers() as $member){
				$player->sendMessage("Member: ".$member);
			}
		}*/
		
		$adam=Utils::getPlayer("adam");
		$steve=Utils::getPlayer("steve");
		$joe=Utils::getPlayer("joe");
		$kris=Utils::getPlayer("kris");
		$kaleb=Utils::getPlayer("kaleb");
		$tom=Utils::getPlayer("tom");
		$ab=Utils::getPlayer("abby");
		if(!is_null($adam)) $this->plugin->getDuelHandler()->addPlayerToQueue($adam, "NoDebuff", true);
		if(!is_null($steve)) $this->plugin->getDuelHandler()->addPlayerToQueue($steve, "BuildUHC", true);
		if(!is_null($kris)) $this->plugin->getDuelHandler()->addPlayerToQueue($kris, "NoDebuff", true);
		if(!is_null($kaleb)) $this->plugin->getDuelHandler()->addPlayerToQueue($kaleb, "NoDebuff", true);
		if(!is_null($tom)) $this->plugin->getDuelHandler()->addPlayerToQueue($tom, "Line", false);
		if(!is_null($ab)) $this->plugin->getDuelHandler()->addPlayerToQueue($ab, "Line", false);
		
		
		
		
		
		//Utils::sendPlayer($adam, "combo", true, true);
		/*
		$elo=300;
		$setcoins=100;
		$space=str_repeat(" ", 4);
				$player->sendMessage($space);
				$player->sendMessage($space."§bMatch Summary");
				$player->sendMessage($space);
				$player->sendMessage($space."  §8You earned");
				$player->sendMessage($space."    §8- §a".$elo." elo");
				$player->sendMessage($space."    §8- §6".$setcoins." coins");
				$player->sendMessage($space);
		switch($args[0]){
			case "0":
			$int=50;
			Utils::earrapePlayer($player);
			break;
			case "1":
			$int=274;
			Utils::playSound($player, $int);
			break;
			case "2":
			$int=51;
			Utils::playSound($player, $int);
			break;
			case "3":
			$int=57;
			Utils::playSound($player, $int);
			break;
			case "4":
			$int=58;
			Utils::playSound($player, $int);
			break;
			case "5":
			$int=59;
			Utils::playSound($player, $int);
			break;
			case "6":
			$int=254;
			Utils::playSound($player, $int);
			break;
			case "7":
			$int=190;
			Utils::playSound($player, $int);
			break;
			case "8":
			$int=102;
			Utils::playSound($player, $int);
			break;
			default:
			return;
		}*/
	}
}
/*
		$player->sendMessage($player->getNameTag());
		$levels=array();
		foreach($this->plugin->getServer()->getLevels() as $level){
			$levels[]=$level->getName();//loaded levels
		}
		$path=$this->plugin->getServer()->getDataPath()."worlds";
		foreach(scandir($path) as $unloadedLevel){
			if(is_dir($path."/".$unloadedLevel) and is_file($path . "/" . $unloadedLevel . "/level.dat") and !in_array($unloadedLevel, $levels)){
				$levels[]="x§c".$unloadedLevel;//unloaded levels
			}
		}
		//$player->sendMessage(implode(", ", $levels));
		if(!isset($args[0])){
			return;
		}
		switch($args[0]){
			case "start":
			$this->plugin->getScheduler()->scheduleRepeatingTask(new CombatTask($this->plugin, $player), 20);
			break;
			case "end":
			CombatTask::setTimer(-1);
			break;
			default:
			return;
		}
	}*/