<?php

declare(strict_types=1);

namespace Zinkil\pc\entities;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\math\RayTraceResult;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\utils\Random;
use pocketmine\level\particle\FlameParticle;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\items\Splash;
use Zinkil\pc\Utils;

class Pearl extends Projectile{
	
	public const NETWORK_ID=self::ENDER_PEARL;
	
	public $height=0.2;
	public $width=0.2;
	protected $gravity=0.1;
	
	public function __construct(Level $level, CompoundTag $nbt, ?Entity $owner=null){
		parent::__construct($level, $nbt, $owner);
		if($owner instanceof Player){
			$this->setPosition($this->add(0, $owner->getEyeHeight()));
			$this->setMotion($owner->getDirectionVector()->multiply(1));
			//$this->handleMotion($this->motion->x, $this->motion->y, $this->motion->z, 1.5, 1.1);
			$this->handleMotion($this->motion->x, $this->motion->y, $this->motion->z, 1.3, 1);
		}
	}
	protected function initEntity():void{
		parent::initEntity();
	}
	public function getResultDamage():int{
		return -1; //no damage
	}
	protected function onHit(ProjectileHitEvent $event):void{
		$owner=$this->getOwningEntity();
		if($owner===null) return;
		$this->level->broadcastLevelEvent($owner, LevelEventPacket::EVENT_PARTICLE_ENDERMAN_TELEPORT);
		$this->level->addSound(new EndermanTeleportSound($owner));
		$owner->teleport($event->getRayTraceResult()->getHitVector());
		$this->level->addSound(new EndermanTeleportSound($owner));
		if($event instanceof ProjectileHitEntityEvent){
			$player=$event->getEntityHit();
			if($player instanceof Player and $owner instanceof Player){
				if($player->getName()!=$owner->getName()){
					$player->attack(new EntityDamageEvent($player, EntityDamageEvent::CAUSE_PROJECTILE, 1));
					$deltaX=$player->x - $this->x;
					$deltaZ=$player->z - $this->z;
					$player->knockBack($owner, 1, $deltaX, $deltaZ, 0.350);
				}
			}
		}
	}
	public function handleMotion(float $x, float $y, float $z, float $f1, float $f2){
		$rand=new Random();
		$f=sqrt($x * $x + $y * $y + $z * $z);
		$x=$x / (float)$f;
		$y=$y / (float)$f;
		$z=$z / (float)$f;
		$x=$x + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
		$y=$y + $rand->nextSignedFloat() * 0.008599999832361937 * (float)$f2;
		$z=$z + $rand->nextSignedFloat() * 0.007499999832361937 * (float)$f2;
		$x=$x * (float)$f1;
		$y=$y * (float)$f1;
		$z=$z * (float)$f1;
		$this->motion->x += $x;
		$this->motion->y += $y * 1.40;//145
		$this->motion->z += $z;
	}
	public function entityBaseTick(int $tickDiff=1):bool{
		$hasUpdate=parent::entityBaseTick($tickDiff);
		$owner=$this->getOwningEntity();
		//$this->getLevel()->addParticle(new FlameParticle($this->asVector3()->add(0,0.5,0)), $this->getLevel()->getPlayers());
		if($owner===null or !$owner->isAlive() or $owner->isClosed() or $this->isCollided){
			$this->flagForDespawn();
		}
		return $hasUpdate;
	}
	public function close():void{
		parent::close();
	}
	public function applyGravity():void{
		if($this->isUnderwater()){
			$this->motion->y += $this->gravity;
		}else{
			parent::applyGravity();
		}
	}
}