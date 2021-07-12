<?php

declare(strict_types=1);

namespace Zinkil\pc\listeners;

use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class AntiCheatListener implements Listener{
	
	private $plugin;
	
	private $reachCooldown=[];
	
	private $cpsCooldown=[];
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}
	/**
	* @priority HIGH
	*/
	public function onEntityDamageByEntity(EntityDamageEvent $event){
		$player=$event->getEntity();
		$cause=$event->getCause();
		switch($cause){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
			$damager=$event->getDamager();
			if(!$player instanceof Player) return;
			if(!$damager instanceof Player) return;
			if($damager->isCreative()) return;
			$damagerpos=$damager->getPosition() ?? new Vector3(0,0,0);
			$playerpos=$player->getPosition() ?? new Vector3(0,0,0);
			$distance=$damagerpos->distance($playerpos);
			$approxdist=7;
			$maxdist=8;
			if($damager->getPing() >= 230){
				$approxdist=$damager->getPing() / 34;
				if($damager->getPing() >= 500){
					$approxdist=$damager->getPing() / 50;
				}
			}
			if($distance > $maxdist){
				$event->setCancelled();
			}
			if($maxdist >= $distance and $distance >= $approxdist){
				$damager->addReachFlag();
				$message=$this->plugin->getStaffUtils()->sendStaffAlert("reach");
				$message=str_replace("{name}", $damager->getName(), $message);
				$message=str_replace("{details}", round($distance, 3), $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.cheatalerts")){
						//$reach=1;
						$reach=1;
						if(!isset($this->reachCooldown[$online->getName()])){
							$this->reachCooldown[$online->getName()]=time();
						}else{
							if($reach > time() - $this->reachCooldown[$online->getName()]){
								$time=time() - $this->reachCooldown[$online->getName()];
							}else{
								$this->reachCooldown[$online->getName()]=time();
								if($online->isAntiCheatOn()) $online->sendMessage($message);
							}
						}
					}
				}
			}
			$cps=$this->plugin->getClickHandler()->getCps($damager);
			$approxcps=23;
			$maxcps=30;
			if($damager->getPing() >= 230){
				$approxcps=25;
				if($damager->getPing() >= 500){
					$approxcps=27;
				}
			}
			if(!$damager->isOp()){
				if($cps >= 65){
					$damager->kick("§cYour CPS is too high.\n§fVia Anti-Cheat", false);
				}
			}
			if($cps >= $maxcps){
				$event->setCancelled();
			}
			if($cps >= $approxcps){
				$damager->addCpsFlag();
				$message=$this->plugin->getStaffUtils()->sendStaffAlert("autoclick");
				$message=str_replace("{name}", $damager->getName(), $message);
				$message=str_replace("{details}", $this->plugin->getClickHandler()->getCps($damager), $message);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $online){
					if($online->hasPermission("pc.staff.cheatalerts")){
						$cps=1;
						if(!isset($this->cpsCooldown[$online->getName()])){
							$this->cpsCooldown[$online->getName()]=time();
						}else{
							if($cps > time() - $this->cpsCooldown[$online->getName()]){
								$time=time() - $this->cpsCooldown[$online->getName()];
							}else{
								$this->cpsCooldown[$online->getName()]=time();
								if($online->isAntiCheatOn()) $online->sendMessage($message);
							}
						}
					}
				}
			}
			break;
			default:
			return;
			break;
		}
	}
}