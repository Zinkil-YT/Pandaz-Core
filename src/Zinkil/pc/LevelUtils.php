<?php

declare(strict_types=1);

namespace Zinkil\pc;

use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class LevelUtils{
	
	public static function increaseLevel($player){
		if(!$player instanceof CPlayer) return;
		Core::getInstance()->getDatabaseHandler()->setLevel(Utils::getPlayerName($player), Core::getInstance()->getDatabaseHandler()->getLevel(Utils::getPlayerName($player)) + 1);
		$level=Core::getInstance()->getDatabaseHandler()->getLevel(Utils::getPlayerName($player));
		$player=Utils::getPlayer($player);
		if($player instanceof Player){
		}
	}
	public static function increaseCurrentXp($player, $reason, $ranked=false, $xpday=false){
		if(!$player instanceof CPlayer) return;
		$rank=Core::getInstance()->getDatabaseHandler()->getRank(Utils::getPlayerName($player));
		switch($reason){
			case "kill":
			$rand=mt_rand(20, 30);
			$kill=$rand;
			$boost=0;
			$rankedbonus=0;
			if($rank=="Voter" or Core::getInstance()->getDatabaseHandler()->voteAccessExists($player)){
				$boost=6;
			}
			if($rank=="VIP"){
				$boost=8;
			}
			if($rank=="MVP"){
				$boost=10;
			}
			if($rank=="Legend"){
				$boost=13;
			}
			if($rank=="Nitro"){
				$boost=11;
			}
			if($rank=="YouTube Mini" or $rank=="Youtube" or $rank=="Famous"){
				$boost=4;
			}
			if($rank=="Trainee" or $rank=="Helper" or $rank=="Mod"){
				$boost=8;
			}
			if($rank=="Admin" or $rank=="Manager" or $rank=="Owner"){
				$boost=12;
			}
			if($ranked===true){
				$rankedbonus=mt_rand(12, 18);
			}else{
				$rankedbonus=0;
			}
			if($xpday===true){
				$kill=$rand * 2;
			}else{
				$kill=$rand;
			}
			$total=$kill + $boost + $rankedbonus;
			$level=Core::getInstance()->getDatabaseHandler()->getLevel(Utils::getPlayerName($player));
			$neededxp=Core::getInstance()->getDatabaseHandler()->getNeededXp(Utils::getPlayerName($player));
			$currentxp=Core::getInstance()->getDatabaseHandler()->getCurrentXp(Utils::getPlayerName($player));
			$totalxp=Core::getInstance()->getDatabaseHandler()->getTotalXp(Utils::getPlayerName($player));
			if($level>=246){
				Core::getInstance()->getDatabaseHandler()->setTotalXp(Utils::getPlayerName($player), $totalxp + $total);
			}else{
				Core::getInstance()->getDatabaseHandler()->setCurrentXp(Utils::getPlayerName($player), $currentxp + $total);
				Core::getInstance()->getDatabaseHandler()->setTotalXp(Utils::getPlayerName($player), $totalxp + $total);
			}
			break;
			case "death":
			$rand=mt_rand(10, 20);
			$death=$rand;
			$boost=0;
			$rankedbonus=0;
			if($rank=="Voter" or Core::getInstance()->getDatabaseHandler()->voteAccessExists($player)){
				$boost=3;
			}
			if($rank=="VIP"){
				$boost=4;
			}
			if($rank=="MVP"){
				$boost=5;
			}
			if($rank=="Legend"){
				$boost=6;
			}
			if($rank=="Nitro"){
				$boost=5;
			}
			if($rank=="YouTube Mini" or $rank=="Youtube" or $rank=="Famous"){
				$boost=4;
			}
			if($rank=="Trainee" or $rank=="Helper" or $rank=="Mod"){
				$boost=4;
			}
			if($rank=="Admin" or $rank=="Manager" or $rank=="Owner"){
				$boost=6;
			}
			if($ranked===true){
				$rankedbonus=mt_rand(3, 10);
			}else{
				$rankedbonus=0;
			}
			if($xpday===true){
				$death=$rand * 2;
			}else{
				$death=$rand;
			}
			$total=$death + $boost + $rankedbonus;
			$level=Core::getInstance()->getDatabaseHandler()->getLevel(Utils::getPlayerName($player));
			$neededxp=Core::getInstance()->getDatabaseHandler()->getNeededXp(Utils::getPlayerName($player));
			$currentxp=Core::getInstance()->getDatabaseHandler()->getCurrentXp(Utils::getPlayerName($player));
			$totalxp=Core::getInstance()->getDatabaseHandler()->getTotalXp(Utils::getPlayerName($player));
			if($level>=146){
				Core::getInstance()->getDatabaseHandler()->setTotalXp(Utils::getPlayerName($player), $totalxp + $total);
			}else{
				Core::getInstance()->getDatabaseHandler()->setCurrentXp(Utils::getPlayerName($player), $currentxp + $total);
				Core::getInstance()->getDatabaseHandler()->setTotalXp(Utils::getPlayerName($player), $totalxp + $total);
			}
			break;
			default:
			return;
		}
	}
	public static function handleXp($player){
		if(!$player instanceof CPlayer) return;
		$level=Core::getInstance()->getDatabaseHandler()->getLevel(Utils::getPlayerName($player));
		$neededxp=Core::getInstance()->getDatabaseHandler()->getNeededXp(Utils::getPlayerName($player));
		if($level>=1){
			$mby=1.05;
			Core::getInstance()->getDatabaseHandler()->setNeededXp(Utils::getPlayerName($player), round($neededxp * $mby, 0));
			Core::getInstance()->getDatabaseHandler()->setCurrentXp(Utils::getPlayerName($player), 0);
		}
		if($level>=146){
			$mby=1.05;
			Core::getInstance()->getDatabaseHandler()->setNeededXp(Utils::getPlayerName($player), round($neededxp * $mby, 0));
			Core::getInstance()->getDatabaseHandler()->setCurrentXp(Utils::getPlayerName($player), 0);
		}
	}
	public static function checkXp($player){
		if(!$player instanceof CPlayer) return;
		$leveledup=false;
		$currentxp=Core::getInstance()->getDatabaseHandler()->getCurrentXp(Utils::getPlayerName($player));
		$neededxp=Core::getInstance()->getDatabaseHandler()->getNeededXp(Utils::getPlayerName($player));
		if($currentxp>=$neededxp){
			self::increaseLevel($player);
			self::handleXp($player);
		}else{
			return;
		}
	}
}