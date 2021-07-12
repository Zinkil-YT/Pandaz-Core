<?php

declare(strict_types=1);

namespace Zinkil\pc\items;

use pocketmine\entity\Entity;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\Player;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Utils;

class Rod extends Durable{
	
	public function __construct($meta=0){
		parent::__construct(Item::FISHING_ROD, $meta, "Fishing Rod");
	}
	public function getMaxStackSize():int{
		return 1;
	}
	public function getCooldownTicks():int{
		return 5;
	}
	public function getMaxDurability():int{
		return 355;
	}
	public function onClickAir(Player $player, Vector3 $directionVector):bool{
		if(!$player->hasItemCooldown($this)){
			$player->resetItemCooldown($this);
			if($player->getFishing()===null){
				$motion=$player->getDirectionVector();
				$motion=$motion->multiply(0.4);
				$nbt=Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $motion);
				$hook=Entity::createEntity("FishingHook", $player->level, $nbt, $player);
				$hook->spawnToAll();
			}else{
				$hook=$player->getFishing();
				if(!is_null($hook)) $hook->flagForDespawn();
				$player->stopFishing();
			}
			$player->broadcastEntityEvent(AnimatePacket::ACTION_SWING_ARM);
			return true;
		}
		return false;
	}
	public function getProjectileEntityType():string{
		return "Hook";
	}
	public function getThrowForce():float{
		return 0.9;
	}
}