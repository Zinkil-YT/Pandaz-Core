<?php

declare(strict_types=1);

namespace Zinkil\pc\handlers;

use pocketmine\Player;
use pocketmine\Server;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class DatabaseHandler{
	
	private $plugin;

	public function __construct(){
		$this->plugin=Core::getInstance();
	}
	public function mutePlayer($player, $reason, $duration, $staff){
		$query=$this->plugin->staff->prepare("INSERT OR REPLACE INTO mutes (player, reason, duration, staff, date) VALUES (:player, :reason, :duration, :staff, :date);");
		$query->bindValue(":player", Utils::getPlayerName($player));
		$query->bindValue(":reason", $reason);
		$query->bindValue(":duration", $duration);
		$query->bindValue(":staff", Utils::getPlayerName($staff));
		$query->bindValue(":date", Utils::getTime());
		$query->execute();
	}
	public function temporaryBanPlayer($player, $reason, $duration, $staff, $givenpoints){
		$query=$this->plugin->staff->prepare("INSERT OR REPLACE INTO temporarybans (player, reason, duration, staff, givenpoints, date) VALUES (:player, :reason, :duration, :staff, :givenpoints, :date);");
		$query->bindValue(":player", Utils::getPlayerName($player));
		$query->bindValue(":reason", $reason);
		$query->bindValue(":duration", $duration);
		$query->bindValue(":staff", Utils::getPlayerName($staff));
		$query->bindValue(":givenpoints", $givenpoints);
		$query->bindValue(":date", Utils::getTime());
		$query->execute();
		
		//$this->setTemporaryBansIssued($staff, $this->getTemporaryBansIssued($staff) + 1);
		//$this->setPointsGiven($staff, $this->getPointsGiven($staff) + $givenpoints);
		$this->setWarnPoints($player, $this->getWarnPoints($player) + $givenpoints);
	}
	public function permanentlyBanPlayer($player, $reason, $staff){
		$query=$this->plugin->staff->prepare("INSERT OR REPLACE INTO permanentbans (player, reason, staff, date) VALUES (:player, :reason, :staff, :date);");
		$query->bindValue(":player", Utils::getPlayerName($player));
		$query->bindValue(":reason", $reason);
		if($staff=="Server"){
			$query->bindValue(":staff", "Server");
		}else{
			$query->bindValue(":staff", Utils::getPlayerName($staff));
		}
		$query->bindValue(":date", Utils::getTime());
		$query->execute();
	}
	public function setTimesJoined($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET timesjoined='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setTimesLeft($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET timesleft='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setPointsGiven($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET pointsgiven='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setMutesIssued($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET mutesissued='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setKicksIssued($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET kicksissued='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setTemporaryBansIssued($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET temporarybansissued='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setPermanentBansIssued($player, $int){
		$query=$this->plugin->staff->exec("UPDATE staffstats SET permanentbansissued='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setWarnPoints($player, $int){
		$query=$this->plugin->staff->exec("UPDATE warnpoints SET points='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function getTimesJoined($player){
		$query=$this->plugin->staff->query("SELECT timesjoined FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["timesjoined"];
	}
	public function getTimesLeft($player){
		$query=$this->plugin->staff->query("SELECT timesleft FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["timesleft"];
	}
	public function getPointsGiven($player){
		$query=$this->plugin->staff->query("SELECT pointsgiven FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["pointsgiven"];
	}
	public function getMutesIssued($player){
		$query=$this->plugin->staff->query("SELECT mutesissued FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["mutesissued"];
	}
	public function getKicksIssued($player){
		$query=$this->plugin->staff->query("SELECT kicksissued FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["kicksissued"];
	}
	public function getTemporaryBansIssued($player){
		$query=$this->plugin->staff->query("SELECT temporarybansissued FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["temporarybansissued"];
	}
	public function getPermanentBansIssued($player){
		$query=$this->plugin->staff->query("SELECT permanentbansissued FROM staffstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["permanentbansissued"];
	}
	public function getWarnPoints($player){
		$query=$this->plugin->staff->query("SELECT points FROM warnpoints WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["points"];
	}
	public function warnPointsAdd($player){
		$check=$this->plugin->staff->query("SELECT player FROM warnpoints WHERE player='".Utils::getPlayerName($player)."';");
		$result=$check->fetchArray(SQLITE3_ASSOC);
		if(empty($result)){
			$query=$this->plugin->staff->prepare("INSERT OR REPLACE INTO warnpoints (player, points) VALUES (:player, :points);");
			$query->bindValue(":player", $player);
			$query->bindValue(":points", 0);
			$query->execute();
		}
	}
	public function isMuted($player):bool{
		$query=$this->plugin->staff->query("SELECT player FROM mutes WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return empty($result)==false;
	}
	public function isTemporarilyBanned($player):bool{
		$query=$this->plugin->staff->query("SELECT player FROM temporarybans WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return empty($result)==false;
	}
	public function isPermanentlyBanned($player):bool{
		$query=$this->plugin->staff->query("SELECT player FROM permanentbans WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return empty($result)==false;
	}
	
	
	
	
	public function rankAdd($player){
		$check=$this->plugin->main->query("SELECT player FROM rank WHERE player='".Utils::getPlayerName($player)."';");
		$result=$check->fetchArray(SQLITE3_ASSOC);
		if(empty($result)){
			$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO rank (player, rank) VALUES (:player, :rank);");
			$query->bindValue(":player", $player);
			$query->bindValue(":rank", "Player");
			$query->execute();
		}
	}
	public function voteAccessCreate($player, $bool){
		$now=time();
		$hour=24 * 3600;
		$duration=$now + $hour;
		$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO voteaccess (player, duration) VALUES (:player, :duration);");
		$query->bindValue(":player", Utils::getPlayerName($player));
		$query->bindValue(":duration", $duration);
		$query->execute();
	}
	public function temporaryRankCreate($player, $temprank, $duration, $oldrank){
		$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO temporaryranks (player, temprank, duration, oldrank) VALUES (:player, :temprank, :duration, :oldrank);");
		$query->bindValue(":player", Utils::getPlayerName($player));
		$query->bindValue(":temprank", $temprank);
		$query->bindValue(":duration", $duration);
		$query->bindValue(":oldrank", $oldrank);
		$query->execute();
	}
	public function levelsAdd($player){
		$check=$this->plugin->main->query("SELECT player FROM levels WHERE player='".Utils::getPlayerName($player)."';");
		$result=$check->fetchArray(SQLITE3_ASSOC);
		if(empty($result)){
			$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO levels (player, level, neededxp, currentxp, totalxp) VALUES (:player, :level, :neededxp, :currentxp, :totalxp);");
			$query->bindValue(":player", $player);
			$query->bindValue(":level", 1);
			$query->bindValue(":neededxp", 100);
			$query->bindValue(":currentxp", 0);
			$query->bindValue(":totalxp", 0);
			$query->execute();
		}
	}
	public function matchStatsAdd($player){
		$check=$this->plugin->main->query("SELECT player FROM matchstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$check->fetchArray(SQLITE3_ASSOC);
		if(empty($result)){
			$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO matchstats (player, elo, wins, losses, elogained, elolost) VALUES (:player, :elo, :wins, :losses, :elogained, :elolost);");
			$query->bindValue(":player", $player);
			$query->bindValue(":elo", 1000);
			$query->bindValue(":wins", 0);
			$query->bindValue(":losses", 0);
			$query->bindValue(":elogained", 0);
			$query->bindValue(":elolost", 0);
			$query->execute();
		}
	}
	public function essentialStatsAdd($player){
		$check=$this->plugin->main->query("SELECT player FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$check->fetchArray(SQLITE3_ASSOC);
		if(empty($result)){
			$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO essentialstats (player, kills, deaths, kdr, killstreak, bestkillstreak, coins, elo) VALUES (:player, :kills, :deaths, :kdr, :killstreak, :bestkillstreak, :coins, :elo);");
			$query->bindValue(":player", $player);
			$query->bindValue(":kills", 0);
			$query->bindValue(":deaths", 0);
			$query->bindValue(":kdr", 0);
			$query->bindValue(":killstreak", 0);
			$query->bindValue(":bestkillstreak", 0);
			$query->bindValue(":coins", 0);
			$query->bindValue(":elo", 0);
			$query->execute();
		}
	}
	public function tempStatisticsAdd($player){
		$check=$this->plugin->main->query("SELECT player FROM temporary WHERE player='".Utils::getPlayerName($player)."';");
		$result=$check->fetchArray(SQLITE3_ASSOC);
		if(empty($result)){
			$query=$this->plugin->main->prepare("INSERT OR REPLACE INTO temporary (player, dailykills, dailydeaths) VALUES (:player, :dailykills, :dailydeaths);");
			$query->bindValue(":player", $player);
			$query->bindValue(":dailykills", 0);
			$query->bindValue(":dailydeaths", 0);
			$query->execute();
		}
	}
	public function setRank($player, $rank){
		$this->plugin->main->exec("UPDATE rank SET rank='$rank' WHERE player='".Utils::getPlayerName($player)."'");
		$player=Utils::getPlayer($player);
		if($player!==null) $this->plugin->getPermissionHandler()->addPermission($player, $rank);
	}
	public function getRank($player){
		$query=$this->plugin->main->query("SELECT rank FROM rank WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return $result["rank"];
	}
	public function countWithRank($type){
		$query=$this->plugin->main->query("SELECT COUNT (player) as count FROM rank WHERE rank='$type';");
		$number=$query->fetchArray();
		return $number['count'];
	}
	public function voteAccessExists($player):bool{
		$query=$this->plugin->main->query("SELECT player FROM voteaccess WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return empty($result)==false;
	}/*
	public function isValueEmpty(string $val):bool{
		$query=$this->plugin->main->query("SELECT rank FROM information WHERE rank='$val';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return empty($result)==false;
	}*/
	public function setLevel($player, $int){
		$this->plugin->main->exec("UPDATE levels SET level='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function setNeededXp($player, $int){
		$this->plugin->main->exec("UPDATE levels SET neededxp='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function setCurrentXp($player, $int){
		$this->plugin->main->exec("UPDATE levels SET currentxp='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function setTotalXp($player, $int){
		$this->plugin->main->exec("UPDATE levels SET totalxp='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function getLevel($player){
		$query=$this->plugin->main->query("SELECT level FROM levels WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["level"];
	}
	public function getNeededXp($player){
		$query=$this->plugin->main->query("SELECT neededxp FROM levels WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["neededxp"];
	}
	public function getCurrentXp($player){
		$query=$this->plugin->main->query("SELECT currentxp FROM levels WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["currentxp"];
	}
	public function getTotalXp($player){
		$query=$this->plugin->main->query("SELECT totalxp FROM levels WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["totalxp"];
	}
	public function setRankedElo($player, $int){
		$this->plugin->main->exec("UPDATE matchstats SET elo='$int' WHERE player='".Utils::getPlayerName($player)."'");
		$this->plugin->getScoreboardHandler()->updateMainLineKdrElo($player);
	}
	public function setWins($player, $int){
		$this->plugin->main->exec("UPDATE matchstats SET wins='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function setLosses($player, $int){
		$this->plugin->main->exec("UPDATE matchstats SET losses='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function setEloGained($player, $int){
		$this->plugin->main->exec("UPDATE matchstats SET elogained='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function setEloLost($player, $int){
		$this->plugin->main->exec("UPDATE matchstats SET elolost='$int' WHERE player='".Utils::getPlayerName($player)."'");
	}
	public function getRankedElo($player){
		$query=$this->plugin->main->query("SELECT elo FROM matchstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return $result["elo"];
	}
	public function getWins($player){
		$query=$this->plugin->main->query("SELECT wins FROM matchstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return $result["wins"];
	}
	public function getLosses($player){
		$query=$this->plugin->main->query("SELECT losses FROM matchstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return $result["losses"];
	}
	public function getEloGained($player){
		$query=$this->plugin->main->query("SELECT elogained FROM matchstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return $result["elogained"];
	}
	public function getEloLost($player){
		$query=$this->plugin->main->query("SELECT elolost FROM matchstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return $result["elolost"];
	}
	public function setKills($player, $int){
		$this->plugin->main->exec("UPDATE essentialstats SET kills='$int' WHERE player='".Utils::getPlayerName($player)."';");
		$this->updateKdr($player);
		$this->plugin->getScoreboardHandler()->updateMainLineKillsDeaths($player);
	}
	public function setDeaths($player, $int){
		$this->plugin->main->exec("UPDATE essentialstats SET deaths='$int' WHERE player='".Utils::getPlayerName($player)."';");
		$newdeaths=$this->getDeaths($player);
		$this->updateKdr($player);
		$this->plugin->getScoreboardHandler()->updateMainLineKillsDeaths($player,);
	}
	public function updateKdr($player){
		$deaths=$this->getDeaths($player->getName());
		$kills=$this->getKills($player->getName());/*
		if($kills!==0 && $deaths==0){
			$this->plugin->main->exec("UPDATE essentialstats SET kdr='$kills'.0 WHERE player='".$player->getName()."'");*/
			if($deaths!==0){
				$kdr=$kills/$deaths;
				$this->plugin->main->exec("UPDATE essentialstats SET kdr='$kdr' WHERE player='".Utils::getPlayerName($player)."';");
				if($kdr!==0){
					$kdr=number_format($kdr, 2);
					$this->plugin->main->exec("UPDATE essentialstats SET kdr='$kdr' WHERE player='".Utils::getPlayerName($player)."';");
				//}
			}
		}
		$this->plugin->getScoreboardHandler()->updateMainLineKdrElo($player);
	}
	public function setKillstreak($player, $int){
		$this->plugin->main->exec("UPDATE essentialstats SET killstreak='$int' WHERE player='".Utils::getPlayerName($player)."';");
		$this->plugin->getScoreboardHandler()->updateMainLineKillstreak($player);
	}
	public function setBestKillstreak($player, $int){
		$this->plugin->main->exec("UPDATE essentialstats SET bestkillstreak='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setCoins($player, $int){
		$this->plugin->main->exec("UPDATE essentialstats SET coins='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setElo($player, $int){
		$this->plugin->main->exec("UPDATE essentialstats SET elo='$int' WHERE player='".Utils::getPlayerName($player)."';");
		$this->plugin->getScoreboardHandler()->updateMainLineKdrElo($player);
	}
	public function getKills($player){
		$query=$this->plugin->main->query("SELECT kills FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["kills"];
	}
	public function getDeaths($player){
		$query=$this->plugin->main->query("SELECT deaths FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["deaths"];
	}
	public function getKdr($player){
		if($player instanceof Player){
			$player=$player->getName();
			}else{
				$player=$player;
		}
		$deaths=$this->getDeaths($player);
		$kills=$this->getKills($player);
		if($deaths!==0){
			$kdr=$kills/$deaths;
			if($kdr!==0){
				return number_format($kdr, 2);
			}
		}
		return $kills.".0";
	}
	public function getKillstreak($player){
		$query=$this->plugin->main->query("SELECT killstreak FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["killstreak"];
	}
	public function getBestKillstreak($player){
		$query=$this->plugin->main->query("SELECT bestkillstreak FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["bestkillstreak"];
	}
	public function getCoins($player){
		$query=$this->plugin->main->query("SELECT coins FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["coins"];
	}
	public function getElo($player){
		if($player instanceof Player){
			$player=$player->getName();
			}else{
				$player=$player;
		}
		$query=$this->plugin->main->query("SELECT elo FROM essentialstats WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["elo"];
	}
	public function setDailyKills($player, int $int){
		if($player instanceof Player){
			$player=$player->getName();
			}else{
				$player=$player;
		}
		$this->plugin->main->exec("UPDATE temporary SET dailykills='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function setDailyDeaths($player, int $int){
		if($player instanceof Player){
			$player=$player->getName();
			}else{
				$player=$player;
		}
		$this->plugin->main->exec("UPDATE temporary SET dailydeaths='$int' WHERE player='".Utils::getPlayerName($player)."';");
	}
	public function getDailyKills($player){
		if($player instanceof Player){
			$player=$player->getName();
			}else{
				$player=$player;
		}
		$query=$this->plugin->main->query("SELECT dailykills FROM temporary WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["dailykills"];
	}
	public function getDailyDeaths($player){
		if($player instanceof Player){
			$player=$player->getName();
			}else{
				$player=$player;
		}
		$query=$this->plugin->main->query("SELECT dailydeaths FROM temporary WHERE player='".Utils::getPlayerName($player)."';");
		$result=$query->fetchArray(SQLITE3_ASSOC);
		return (int) $result["dailydeaths"];
	}
	
	
	
	
	public function topLevels(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM levels ORDER BY level DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getLevel($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getLevel($viewer)."§r\n".$message;
	}
	public function topElo(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM matchstats ORDER BY elo DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getRankedElo($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n"; 
				}
				++$i;
			}
		}
		return "§3You - ".$this->getRankedElo($viewer)."§r\n".$message;
	}
	public function topWins(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM matchstats ORDER BY wins DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getWins($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getWins($viewer)."§r\n".$message;
	}
	public function topLosses(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM matchstats ORDER BY losses DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getLosses($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getLosses($viewer)."§r\n".$message;
	}
	public function topKills(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM essentialstats ORDER BY kills DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getKills($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getKills($viewer)."§r\n".$message;
	}
	public function topDeaths(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM essentialstats ORDER BY deaths DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getDeaths($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getDeaths($viewer)."§r\n".$message;
	}
	public function topKdr(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM essentialstats ORDER BY kdr DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getKdr($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getKdr($viewer)."§r\n".$message;
	}
	public function topKillstreaks(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM essentialstats ORDER BY bestkillstreak DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getBestKillstreak($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getBestKillstreak($viewer)."§r\n".$message;
	}
	public function topDailyKills(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM temporary ORDER BY dailykills DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getDailyKills($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getDailyKills($viewer)."§r\n".$message;
	}
	public function topDailyDeaths(string $viewer){
		$query=$this->plugin->main->query("SELECT * FROM temporary ORDER BY dailydeaths DESC LIMIT 10;");
		$message="";
		$i=0;
		while($resultArr=$query->fetchArray(SQLITE3_ASSOC)){
			$j=$i + 1;
			$player=$resultArr['player'];
			$val=$this->getDailyDeaths($player);
			if(Utils::isShowInLeaderboardsEnabled($player)==true){
				if($j===1){
					$message.="§8#1 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===2){
					$message.="§8#2 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j===3){
					$message.="§8#3 §7".$player." §8-§7 ".$val."\n"; 
				}
				if($j!==1 and $j!==2 and $j!==3){
					$message.="§8#".$j." §7".$player." §8-§7 ".$val."\n";
				}
				++$i;
			}
		}
		return "§3You - ".$this->getDailyDeaths($viewer)."§r\n".$message;
	}
}