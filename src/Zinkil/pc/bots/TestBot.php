<?php

/*
Assignment    Same as:
$a += $b     $a = $a + $b    Addition
$a -= $b     $a = $a - $b     Subtraction
$a *= $b     $a = $a * $b     Multiplication
$a /= $b     $a = $a / $b    Division
$a %= $b     $a = $a % $b    Modulus
*/

namespace Zinkil\pc\bots;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Creature;
use pocketmine\entity\NPC;
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
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use Zinkil\pc\Utils;
use Zinkil\pc\duels\groups\BotDuelGroup;

class TestBot extends Creature implements NPC{
	
	public $width = 0.6;
	public $height = 1.8;
	
	const ATTACK_COOLDOWN=8;
	const REACH_DISTANCE=3;
	const ACCURACY=40;
	const POT_CHANCE=1;//995
	const POT_WAIT=20 * 8;//8 seconds
	public $target=null;
	public $duel=null;
	public $deactivated=false;
	public $refilling=false;
	public $potsUsed=0;
	public $refillTicks=0;
	public $randomPosition=null;
	public $newLocTicks=60;
	//public $gravity=0.0072;
	public $potTicks=self::POT_WAIT;
	public $jumpTicks=10;
	public $attackcooldown=self::ATTACK_COOLDOWN;
	public $reachDistance=self::REACH_DISTANCE;
	public $safeDistance=1.5;
	public $attackDamage=8;
	public $speed=0.55;
	public $startingHealth=20;
	
	public const NETWORK_ID=self::VILLAGER;
	
	public $name="TestBot";
	
	public function initEntity():void{
		parent::initEntity();
	}
	public function saveNBT() : void{
		parent::saveNBT();
	}
	public function getName():string{
		return $this->name;
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
		$xzKB=0.388;
		$yKb=0.485;
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
			$this->move($motion->x * 1.60, $motion->y * 1.80, $motion->z * 1.60);
		}
	}
	public function entityBaseTick(int $tickDiff=1):bool{
		parent::entityBaseTick($tickDiff);
		if(!$this->isAlive()){
			if(!$this->closed) $this->flagForDespawn();
			return false;
		}
		$this->setNametag("§e".$this->getNameTag()." §f[§c".round($this->getHealth(), 1)."§f]");
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