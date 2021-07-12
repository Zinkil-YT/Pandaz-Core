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
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class DistanceListener implements Listener{
	
	public $plugin;
	
	public function __construct(){
		$this->plugin=Core::getInstance();
	}
	/**
	* @priority HIGH
	*/
	public function onEntityDamageByEntity(EntityDamageEvent $event){
		$player=$event->getEntity();
		$cause=$event->getCause();
		$level=$player->getLevel()->getName();
		switch($cause){
			case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
			$damager=$event->getDamager();
			$dlevel=$damager->getLevel()->getName();
			if(!$player instanceof Player) return;
			if(!$damager instanceof Player) return;
			if($damager->isCreative()) return;
			if($level==Core::LOBBY) return;
			if($dlevel==Core::LOBBY) return;
			$damagerpos=$damager->getPosition() ?? new Vector3(0,0,0);
			$playerpos=$player->getPosition() ?? new Vector3(0,0,0);
			$distance=$damagerpos->distance($playerpos);
			$health=round($player->getHealth(), 1);
			$playername=$player->getDisplayName();
			$damagername=$damager->getDisplayName();
			$player->sendPopup("§e".$damagername."§e: §7".$distance);
			$damager->sendPopup("§f".$health."§f/20 §l§8| "."§r§cDistance: §7".$distance);
		}
	}
}