<?php

namespace Zinkil\pc\bots;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\block\{Slab, Stair, Flowable};
use pocketmine\entity\Attribute;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\block\Liquid;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat as C;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use Zinkil\pc\Utils;
use Zinkil\pc\duels\groups\BotDuelGroup;

class MediumBot extends Human{
	
	const ATTACK_COOLDOWN=6;
	const REACH_DISTANCE=3;
	const LOW_REACH_DISTANCE=0.5;
	const ACCURACY=50;
	const POT_CHANCE=973;
	const POT_WAIT=20 * 5;//5 seconds
	
	public $name="Medium Bot";
	public $target=null;
	public $duel=null;
	public $deactivated=false;
	public $potsUsed=0;
	public $findNewTargetTicks=0;
	public $randomPosition=null;
	public $newLocTicks=60;
	public $gravity=0.0066;
	public $potTicks=self::POT_WAIT;
	public $jumpTicks=10;
	public $attackcooldown=self::ATTACK_COOLDOWN;
	public $reachDistance=self::REACH_DISTANCE;
	public $safeDistance=2.3;
	public $attackDamage=8;
	public $speed=0.55;
	public $startingHealth=20;
	
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->setMaxHealth($this->startingHealth);
		$this->setHealth($this->startingHealth);
		$this->setNametag($this->name);
		$this->generateRandomPosition();
	}
	public function getType(){
		return "MediumBot";
	}
	public function setTarget($player){
		$target=$player;
		$this->target=($target!=null ? $target->getName():"");
	}
	public function hasTarget():bool{
		if($this->target===null) return false;
		$target=$this->getTarget();
		if($target===null) return false;
		$player=$this->getTarget();
		return !$player->isSpectator();
	}
	public function getTarget(){
		return Server::getInstance()->getPlayerExact($this->target);
	}
	public function setDuel(BotDuelGroup $duel){
		$this->duel=$duel;
	}
	public function hasDuel():bool{
		return $this->duel!==null;
	}
	public function getDuel(){
		return $this->duel;
	}
	private function isDeactivated():bool{
		return $this->deactivated===true;
	}
	public function setDeactivated(bool $result=true){
		$this->deactivated=$result;
	}
	private function isRefilling():bool{
		return $this->refilling===true;
	}
	public function setRefilling(bool $result=true){
		$this->refilling=$result;
	}
	public function getName():string{
		return $this->name;
	}
	public function getNameTag():string{
		return $this->name;
	}
	public function entityBaseTick(int $tickDiff=1):bool{
		parent::entityBaseTick($tickDiff);
		if($this->isDeactivated()) return false;
		if(!$this->isAlive()){
			if(!$this->closed) $this->flagForDespawn();
			return false;
		}
		$this->setNametag("§e".$this->getNameTag()." §f[§c".round($this->getHealth(), 1)."§f]");
		if($this->hasTarget()){
			if($this->getLevel()->getName()==$this->getTarget()->getLevel()->getName()){
				return $this->attackTarget();
			}else{
				$this->setDeactivated();
				if($this->hasDuel()) $this->getDuel()->endDuelPrematurely();
				if(!$this->closed) $this->flagForDespawn();
			}
		}else{
			$this->setDeactivated();
			if($this->hasDuel()) $this->getDuel()->endDuelPrematurely();
			if(!$this->closed) $this->flagForDespawn();
			return false;
		}
		if($this->potTicks > 0) $this->potTicks--;
		if($this->jumpTicks > 0) $this->jumpTicks--;
		if($this->newLocTicks > 0) $this->newLocTicks--;
		if(!$this->isOnGround()){
			if($this->motion->y > -$this->gravity * 1){ //default is 4
				$this->motion->y=-$this->gravity * 1;
			}else{
				$this->motion->y += $this->isUnderwater() ? $this->gravity:-$this->gravity;
			}
		}else{
			$this->motion->y -= $this->gravity;
		}
		if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
		if($this->shouldPot()) $this->pot();
		if($this->shouldJump()) $this->jump();
		if($this->atRandomPosition() or $this->newLocTicks===0){
			$this->generateRandomPosition();
			$this->newLocTicks=60;
			return true;
		}
		$position=$this->getRandomPosition();
		$x=$position->x - $this->getX();
		$y=$position->y - $this->getY();
		$z=$position->z - $this->getZ();
		if($x * $x + $z * $z < 4 + $this->getScale()){
			$this->motion->x=0;
			$this->motion->z=0;
		}else{
			$this->motion->x=$this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
			$this->motion->z=$this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
		}
		$this->yaw=rad2deg(atan2(-$x, $z));
		$this->pitch=0;
		if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
		if($this->shouldPot()) $this->pot();
		if($this->shouldJump()) $this->jump();
		if($this->isAlive()) $this->updateMovement();
		return $this->isAlive();
	}
	public function attackTarget(){
		if($this->isDeactivated()) return;
		if(!$this->isAlive()){
			if(!$this->closed) $this->flagForDespawn();
			return;
		}
		$target=$this->getTarget();
		if($target===null){
			$this->target=null;
			return true;
		}
		if($this->getLevel()->getName()!=$target->getLevel()->getName()){
			$this->setDeactivated();
			if($this->hasDuel()) $this->getDuel()->endDuelPrematurely();
			if(!$this->closed) $this->flagForDespawn();
		}
		$x=$target->x - $this->x;
		$y=$target->y - $this->y;
		$z=$target->z - $this->z;
		if($this->potTicks > 0) $this->potTicks--;
		if($this->jumpTicks > 0) $this->jumpTicks--;
		if(!$this->isOnGround()){
			$this->reachDistance=self::LOW_REACH_DISTANCE;
			if($this->distance($target) <= 5){
				$this->motion->x=$this->getSpeed() * 0.15 * -$x;
				$this->motion->z=$this->getSpeed() * 0.15 * -$z;
			}
			if($this->motion->y > -$this->gravity * 1){ //default is 4
				$this->motion->y=-$this->gravity * 1;
			}else{
				$this->motion->y += $this->isUnderwater() ? $this->gravity:-$this->gravity;
			}
		}else{
			$this->reachDistance=self::REACH_DISTANCE;
			$this->motion->y -= $this->gravity;
		}
		if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
		if($this->shouldPot()) $this->pot();
		if($this->shouldJump()) $this->jump();
		if($this->distance($target) <= $this->safeDistance){
			$this->motion->x=0;
			$this->motion->z=0;
		}else{
			if($target->isSprinting()){
				$this->motion->x=$this->getSpeed() * 0.20 * ($x / (abs($x) + abs($z)));
				$this->motion->z=$this->getSpeed() * 0.20 * ($z / (abs($x) + abs($z)));
			}else{
				$this->motion->x=$this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
				$this->motion->z=$this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
			}
		}
		$this->yaw=rad2deg(atan2(-$x, $z));
		$this->pitch=rad2deg(-atan2($y, sqrt($x * $x + $z * $z)));
		if($this->shouldPot()) $this->pot();
		if($this->shouldJump()) $this->jump();
		if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x, $this->motion->y, $this->motion->z);
		if(0>=$this->attackcooldown){
			if($this->distance($target) <= $this->reachDistance){
				$event=new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getBaseAttackDamage());
				if($this->isAlive()) $this->broadcastEntityEvent(4);
				if(mt_rand(0, 100) <= self::ACCURACY){
					$target->attack($event);
					//$target->sendMessage("Hit");
					$volume=0x10000000 * (min(30, 10) / 5);
					$target->getLevel()->broadcastLevelSoundEvent($target->asVector3(), LevelSoundEventPacket::SOUND_ATTACK, (int) $volume);
				}else{
					//$target->sendMessage("Missed");
					$volume=0x10000000 * (min(30, 10) / 5);
					$target->getLevel()->broadcastLevelSoundEvent($this->asVector3(), LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE, (int) $volume);
				}
				$this->attackcooldown=self::ATTACK_COOLDOWN;
			}
		}
		if($this->isAlive()) $this->updateMovement();
		$this->attackcooldown--;
		return $this->isAlive();
	}
	public function attack(EntityDamageEvent $source):void{
		parent::attack($source);
		if($source->isCancelled()){
			$source->setCancelled();
			return;
		}
		if($source instanceof EntityDamageByEntityEvent){
			$killer=$source->getDamager();
			if($killer instanceof Player){
				if($killer->isSpectator()){
					$source->setCancelled(true);
					return;
				}
				$deltaX=$this->x - $killer->x;
				$deltaZ=$this->z - $killer->z;
				$this->knockBack($killer, 0, $deltaX, $deltaZ);
			}
		}
	}
	public function knockBack($damager, float $damage, float $x, float $z, float $base=0.4):void{
		$xzKB=0.390;
		$yKb=0.388;
		$f=sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > $this->getAttributeMap()->getAttribute(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
			$f=1 / $f;
			$motion=clone $this->motion;
			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $xzKB;
			$motion->y += $yKb;
			$motion->z += $z * $f * $xzKB;
			if($motion->y > $yKb){
				$motion->y = $yKb;
			}
			if($this->isAlive() and !$this->isClosed()) $this->move($motion->x * 1.60, $motion->y * 1.40, $motion->z * 1.60);
		}
	}
	public function kill():void{
		parent::kill();
	}
	public function atRandomPosition(){
		return $this->getRandomPosition()==null or $this->distance($this->getRandomPosition()) <= 2;
	}
	public function getRandomPosition(){
		return $this->randomPosition;
	}
	public function generateRandomPosition(){
		$minX=$this->getFloorX() - 8;
		$minY=$this->getFloorY() - 8;
		$minZ=$this->getFloorZ() - 8;
		$maxX=$minX + 16;
		$maxY=$minY + 16;
		$maxZ=$minZ + 16;
		$level=$this->getLevel();
		for($attempts=0; $attempts < 16; ++$attempts){
			$x=mt_rand($minX, $maxX);
			$y=mt_rand($minY, $maxY);
			$z=mt_rand($minZ, $maxZ);
			while($y >= 0 and !$level->getBlockAt($x, $y, $z)->isSolid()){
				$y--;
			}
			if($y < 0){
				continue;
			}
			$blockUp=$level->getBlockAt($x, $y + 1, $z);
			$blockUp2=$level->getBlockAt($x, $y + 2, $z);
			if($blockUp->isSolid() or $blockUp instanceof Liquid or $blockUp2->isSolid() or $blockUp2 instanceof Liquid){
				continue;
			}
			break;
		}
		$this->randomPosition=new Vector3($x, $y + 1, $z);
	}
	public function getSpeed(){
		return ($this->isUnderwater() ? $this->speed / 2:$this->speed);
	}
	public function getBaseAttackDamage(){
		return $this->attackDamage;
	}
	public function getFrontBlock($y=0){
		$dv=$this->getDirectionVector();
		$pos=$this->asVector3()->add($dv->x * $this->getScale(), $y + 1, $dv->z * $this->getScale())->round();
		return $this->getLevel()->getBlock($pos);
	}
	public function shouldJump(){
		if($this->jumpTicks > 0) return false;
		if(!$this->isOnGround()) return false;
		return $this->isCollidedHorizontally or 
		($this->getFrontBlock()->getId()!=0 or $this->getFrontBlock(-1) instanceof Stair) or
		($this->getLevel()->getBlock($this->asVector3()->add(0,-0,5)) instanceof Slab and
		(!$this->getFrontBlock(-0.5) instanceof Slab and $this->getFrontBlock(-0.5)->getId()!=0)) and
		$this->getFrontBlock(1)->getId()==0 and 
		$this->getFrontBlock(2)->getId()==0 and 
		!$this->getFrontBlock() instanceof Flowable and
		$this->jumpTicks==0;
	}
	public function shouldPot(){
		if($this->potsUsed >= 25) return false;
		if($this->potTicks > 0) return false;
		return mt_rand(7, 9) >= $this->getHealth();
	}
	public function getJumpMultiplier(){
		return 64;
		if($this->getFrontBlock() instanceof Slab or $this->getFrontBlock() instanceof Stair or $this->getLevel()->getBlock($this->asVector3()->subtract(0,0.5)->round()) instanceof Slab and $this->getFrontBlock()->getId()!=0){
			$fb=$this->getFrontBlock();
			if($fb instanceof Slab and $fb->getDamage() & 0x08 > 0) return 8;
			if($fb instanceof Stair and $fb->getDamage() & 0x04 > 0) return 8;
			return 16;
		}
		return 32;
	}
	public function jump():void{
		if($this->jumpTicks > 0) return;
		$this->motion->y=$this->gravity * $this->getJumpMultiplier();
		if($this->isAlive() and !$this->isClosed()) $this->move($this->motion->x * 1.15, $this->motion->y, $this->motion->z * 1.15);
		$this->jumpTicks=10; //($this->getJumpMultiplier()==4 ? 2:5);
	}
	public function pot():void{
		if($this->potsUsed >= 25) return;
		if(mt_rand(0, 1000) > self::POT_CHANCE){
			Utils::instantPots(Item::SPLASH_POTION, $this, true);
			$this->potTicks=self::POT_WAIT;
			$this->potsUsed++;
		}
	}
}