<?php
declare(strict_types=1);

namespace Zinkil\pc\entities;

use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class PotionEntity extends Human{

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
	}
	public function saveNBT():void{
		parent::saveNBT();
	}
	public function onUpdate(int $currentTick):bool{
		$this->yaw += 2.5;
		$this->pitch += 5.5;
		$this->move($this->motion->x, $this->motion->y, $this->motion->z);
		$this->updateMovement();
		foreach($this->getViewers() as $player){
			$pk=new SetActorDataPacket();
			$pk->entityRuntimeId=$this->getId();
			$pk->metadata=[self::DATA_NAMETAG => [self::DATA_TYPE_STRING, $this->getNameTag()]];
			$player->dataPacket($pk);
        }
        $this->spawnToAll();
		return parent::onUpdate($currentTick);
	}
    public function attack(EntityDamageEvent $source):void{
		$source->setCancelled();
	}
}