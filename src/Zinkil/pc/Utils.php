<?php

declare(strict_types=1);

namespace Zinkil\pc;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\entity\EntityIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\level\Position;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\SplashPotion as DefaultSplashPotion;
use pocketmine\entity\projectile\SplashPotion as ProjectileSplashPotion;
use pocketmine\item\MushroomStew;
use pocketmine\item\EnderPearl;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\HugeExplodeParticle;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\utils\Color;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Kits;
use Zinkil\pc\entities\Hook;
use Zinkil\pc\PandazChunkLoader;
use Zinkil\pc\multiver\PMPlayer;
use Zinkil\pc\forms\{SimpleForm, CustomForm, ModalForm};
use Zinkil\pc\bots\{EasyBot, MediumBot, HardBot, HackerBot};

class Utils{
	
	const SWISH_SOUNDS=[LevelSoundEventPacket::SOUND_ATTACK => true, LevelSoundEventPacket::SOUND_ATTACK_STRONG => true];
	
	public static function resetStats($player){
		Core::getInstance()->main->exec("UPDATE essentialstats SET kills=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE essentialstats SET deaths=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE essentialstats SET kdr=0.0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE essentialstats SET killstreak=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE essentialstats SET bestkillstreak=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE essentialstats SET coins=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE essentialstats SET elo=0 WHERE player='".$player."';");

		Core::getInstance()->main->exec("UPDATE matchstats SET elo=1000 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE matchstats SET wins=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE matchstats SET losses=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE matchstats SET elogained=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE matchstats SET elolost=0 WHERE player='".$player."';");

		Core::getInstance()->main->exec("UPDATE temporary SET dailykills=0 WHERE player='".$player."';");
		Core::getInstance()->main->exec("UPDATE temporary SET dailydeaths=0 WHERE player='".$player."';");
	}
	public static function createPotion($player){
		$motion=$player->getDirectionVector();
		$nbt=Entity::createBaseNBT($player->add(0, 0, 0), $motion);
		$pot=Utils::preferredPot($player);
		switch($pot){
			case "default":
			$entity=Entity::createEntity("DefaultPotion", $player->level, $nbt, $player);
			break;
			case "fast":
			$entity=Entity::createEntity("FastPotion", $player->level, $nbt, $player);
			break;
			default:
			$entity=Entity::createEntity("DefaultPotion", $player->level, $nbt, $player);
			break;
		}
		if($entity instanceof Projectile){
			$event=new ProjectileLaunchEvent($entity);
			$event->call();
			if($event->isCancelled() or $player->getGamemode()===3){
				$entity->kill();
			}else{
				$entity->spawnToAll();
				$itemInHand=$player->getInventory()->getItemInHand();
				if($itemInHand->getId()===Item::SPLASH_POTION){
					$player->getInventory()->setItemInHand(Item::get(0));
				}
			}
		}
	}
	public static function createPearl($player){
		$motion=$player->getDirectionVector();
		$nbt=Entity::createBaseNBT($player->add(0, 0, 0), $motion);
		$entity=Entity::createEntity("CPPearl", $player->level, $nbt, $player);
		if($entity instanceof Projectile){
			$event=new ProjectileLaunchEvent($entity);
			$event->call();
			if($event->isCancelled() or $player->getGamemode()===3){
				$entity->kill();
			}else{
				$entity->spawnToAll();
			}
		}
	}
	public static function createBot($player, string $type, float $x, float $y, float $z, Level $level){
		$player=self::getPlayer($player);
		if($player===null) return;
		$skin=$player->namedtag->getTag("Skin");
		if($skin===null){
			$player->sendMessage("§cYou must use a skin to start a bot duel.");
			return;
		}
		switch($type){
			case "easy":
			$vec=new Vector3($x, $y, $z);
			$nbt=Entity::createBaseNBT($vec, null, 2, 2);
			$player=self::getPlayer($player);
			if($player instanceof Player) $nbt->setTag($skin);
			$bot=new EasyBot($level, $nbt);
			$bot->setNameTagAlwaysVisible(true);
			$bot->spawnToAll();
			return $bot;
			break;
			case "medium":
			$vec=new Vector3($x, $y, $z);
			$nbt=Entity::createBaseNBT($vec, null, 2, 2);
			$player=self::getPlayer($player);
			if($player instanceof Player) $nbt->setTag($skin);
			$bot=new MediumBot($level, $nbt);
			$bot->setNameTagAlwaysVisible(true);
			$bot->spawnToAll();
			return $bot;
			break;
			case "hard":
			$vec=new Vector3($x, $y, $z);
			$nbt=Entity::createBaseNBT($vec, null, 2, 2);
			$player=self::getPlayer($player);
			if($player instanceof Player) $nbt->setTag($skin);
			$bot=new HardBot($level, $nbt);
			$bot->setNameTagAlwaysVisible(true);
			$bot->spawnToAll();
			return $bot;
			break;
			case "hacker":
			$vec=new Vector3($x, $y, $z);
			$nbt=Entity::createBaseNBT($vec, null, 2, 2);
			$player=self::getPlayer($player);
			if($player instanceof Player) $nbt->setTag($skin);
			$bot=new HackerBot($level, $nbt);
			$bot->setNameTagAlwaysVisible(true);
			$bot->spawnToAll();
			return $bot;
			break;
			default:
			return;
			break;
		}
	}
	public static function broadcastPacketToViewers(CPlayer $inPlayer, DataPacket $packet, ?callable $callable=null, ?array $viewers=null):void{
		$viewers=$viewers ?? $inPlayer->getLevel()->getViewersForPosition($inPlayer->asVector3());
		foreach($viewers as $viewer){
			if($viewer->isOnline()){
				if($callable!==null and !$callable($viewer, $packet)){
					continue;
				}
				$viewer->batchDataPacket($packet);
			}
		}
	}
	public static function transferPlayers(array $players){
		foreach($players as $player){
			$player->transfer(Core::IP, 19132, "");
			Core::getInstance()->getLogger()->notice("Transferring ".$player->getName()." due to server stop...");
		}
		Core::getInstance()->getLogger()->notice("All players have been transferred.");
	}
	public static function currentTimeMillis():float{
		$time=microtime(true);
		return $time * 1000;
	}
	public static function secondsToTicks(int $seconds):int{
		return $seconds * 20;
	}
	public static function minutesToTicks(int $minutes):int{
		return $minutes * 1200;
	}
	public static function hoursToTicks(int $hours):int{
		return $hours * 72000;
	}
	public static function ticksToSeconds(int $tick):int{
		return intval($tick / 20);
	}
	public static function ticksToMinutes(int $tick):int{
		return intval($tick / 1200);
	}
	public static function ticksToHours(int $tick):int{
		return intval($tick / 72000);
	}
	public static function onChunkGenerated(Level $level, int $x, int $z, callable $callable):void{
		if($level->isChunkPopulated($x, $z)){
			$callable();
			return;
		}
		$level->registerChunkLoader(new PracticeChunkLoader($level, $x, $z, $callable), $x, $z, true);
	}
	public static function str_contains(string $needle, string $haystack, bool $use_mb=false):bool{
		$result=false;
		$type=($use_mb === true) ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);
		if(is_bool($type)){
			$result=$type;
		}elseif (is_int($type)){
			$result=$type > -1;
		}
		return $result;
	}
	public static function str_replace(string $haystack, array $values):string{
		$result=$haystack;
		$keys=array_keys($values);
		foreach($keys as $value){
			$value=strval($value);
			$replaced=strval($values[$value]);
			if(self::str_contains($value, $haystack)){
				$result=str_replace($value, $replaced, $result);
			}
		}
		return $result;
	}
	public static function clearEntities(Level $level, bool $proj=false, bool $all=false):void{
		$entities=$level->getEntities();
		foreach($entities as $entity){
			if(!$entity instanceof CPlayer) $entity->close();
			//if($entity instanceof CPlayer) $exec=false;
			
			/*
			elseif ($all === false and $entity instanceof FishingHook) $exec=false;
            elseif ($all === false and $entity instanceof \pocketmine\entity\projectile\EnderPearl) $exec=false;
            elseif ($all === false and $entity instanceof \pocketmine\entity\projectile\SplashPotion) $exec=false;
            elseif ($all === false and $entity instanceof Arrow) $exec=$proj;
			*/
			
		}
	}
	public static function formatLevel($level){
		$format="§8".$level;
		if($level>=0){
			$format="§8".$level;
		}
		if($level>=20){
			$format="§7".$level;
		}
		if($level>=40){
			$format="§3".$level;
		}
		if($level>=60){
			$format="§5".$level;
		}
		if($level>=80){
			$format="§4".$level;
		}
		$level="".$level."";
		if($level>=100){
			$IIIdigit=substr($level, -1, 1);
			$IIdigit=substr($level, -2, 1);
			$Idigit=substr($level, -3, 1);
			$format="§e".$Idigit."§6".$IIdigit."§c".$IIIdigit;
		}
		if($level>=120){
			$IIIdigit=substr($level, -1, 1);
			$IIdigit=substr($level, -2, 1);
			$Idigit=substr($level, -3, 1);
			$format="§6".$Idigit."§a".$IIdigit."§b".$IIIdigit;
		}
		return $format;
	}
	public static function getClass($elo){
		$format="§6Rookie I";
		if($elo>=1000 && $elo<1200){
			$format="§6Rookie I";
		}
		if($elo>=1200 && $elo<1400){
			$format="§6Rookie II";
		}
		if($elo>=1400 && $elo<1600){
			$format="§6Rookie III";
		}
		if($elo>=1600 && $elo<1800){
			$format="§6Rookie IV";
		}
		if($elo>=1800 && $elo<2000){
			$format="§6Rookie V";
		}
		return $format;
	}
	public static function testPots(Player $player){
		if(is_null($player)) return;
		$directionVector=$player->getDirectionVector();
		$nbt=Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);
		$entity=Entity::createEntity(ProjectileSplashPotion::class, $player->getLevel(), $nbt, $player);
		$entity->setMotion($entity->getMotion()->multiply(50));
		if($entity instanceof Projectile){
			$event=new ProjectileLaunchEvent($entity);
			$event->call();
			if($event->isCancelled()){
				$entity->kill();
			}
			else{
				$entity->spawnToAll();
			}
		}
		else{
			$entity->spawnToAll();
		}
	}
	public static function setGlobalMute(bool $bool){
		Core::getInstance()->globalMute=$bool;
	}
	public static function getGlobalMute():bool{
		return Core::getInstance()->globalMute;
	}
	public static function initPlayer($player){
		if(is_null($player)) return;
		$ip=$player->getAddress();
		$cid=$player->getClientId();
		$pathstats=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(!file_exists($pathstats)){
			$datastats=array(
			'tags' => [],
			'custom-tags' => [],
			'tag-slots' => ["one"],
			'capes' => [],
			'kill-particles' => [],
			'selected-cape' => false,
			'selected-kill-particle' => false,
			'is-staff' => false,
			'scoreboard' => true,
			'low-fps-mode' => false,
			'pot-feedback' => false,
			'show-in-leaderboards' => true,
			'auto-requeue' => false,
			'auto-rekit' => false,
			'auto-sprint' => false,
			'cps-counter'=> true,
			'combat-counter'=> true,
			'swing-sounds'=> false,
			'pot-splash-color' => "default",
			'particle-mod' => 'off',
			'preferred-pot' => 'default',
			'permissions' => []);
			yaml_emit_file($pathstats, $datastats);
		}else{
			$data=yaml_parse_file($pathstats);
			$array=self::getPlayerData($player);
			$edit=false;
			if(!isset($data['tags'])){
				$data['tags']=[];
				$edit=true;
			}
			if(!isset($data['custom-tags'])){
				$data['custom-tags']=[];
				$edit=true;
			}
			if(!isset($data['tag-slots'])){
				$data['tag-slots']=['one'];
				$edit=true;
			}
			if(!isset($data['capes'])){
				$data['capes']=[];
				$edit=true;
			}
			if(!isset($data['kill-particles'])){
				$data['kill-particles']=[];
				$edit=true;
			}
			if(!isset($data['selected-cape'])){
				$data['selected-cape']=false;
				$edit=true;
			}
			if(!isset($data['selected-kill-particle'])){
				$data['selected-kill-particle']=false;
				$edit=true;
			}
			if(!isset($data['is-staff'])){
				$data['is-staff']=false;
				$edit=true;
			}
			if(!isset($data['scoreboard'])){
				$data['scoreboard']=true;
				$edit=true;
			}
			if(!isset($data['low-fps-mode'])){
				$data['low-fps-mode']=false;
				$edit=true;
			}
			if(!isset($data['pot-feedback'])){
				$data['pot-feedback']=false;
				$edit=true;
			}
			if(!isset($data['show-in-leaderboards'])){
				$data['show-in-leaderboards']=true;
				$edit=true;
			}
			if(!isset($data['auto-requeue'])){
				$data['auto-requeue']=false;
				$edit=true;
			}
			if(!isset($data['auto-rekit'])){
				$data['auto-rekit']=false;
				$edit=true;
			}
			if(!isset($data['auto-sprint'])){
				$data['auto-sprint']=false;
				$edit=true;
			}
			if(!isset($data['cps-counter'])){
				$data['cps-counter']=true;
				$edit=true;
			}
			if(!isset($data['combat-counter'])){
				$data['combat-counter']=true;
				$edit=true;
			}
			if(!isset($data['swing-sounds'])){
				$data['swing-sounds']=true;
				$edit=true;
			}
			if(!isset($data['pot-splash-color'])){
				$data['pot-splash-color']="default";
				$edit=true;
			}
			if(!isset($data['particle-mod'])){
				$data['particle-mod']="off";
				$edit=true;
			}
			if(!isset($data['preferred-pot'])){
				$data['preferred-pot']="default";
				$edit=true;
			}
			if(!isset($data['clan-tag'])){
				$data['clan-tag']="";
				$edit=true;
			}
			if($edit===true){
				yaml_emit_file($pathstats, $data);
			}
		}
	}
	public static function isScoreboardEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['scoreboard'])){
				$result=boolval($data['scoreboard']);
			}
		}
		return $result;
	}
	public static function isPotFeedbackEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['pot-feedback'])){
				$result=boolval($data['pot-feedback']);
			}
		}
		return $result;
	}
	public static function isShowInLeaderboardsEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['show-in-leaderboards'])){
				$result=boolval($data['show-in-leaderboards']);
			}
		}
		return $result;
	}
	public static function isAutoRequeueEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['auto-requeue'])){
				$result=boolval($data['auto-requeue']);
			}
		}
		return $result;
	}
	public static function isAutoRekitEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['auto-rekit'])){
				$result=boolval($data['auto-rekit']);
			}
		}
		return $result;
	}
	public static function isAutoSprintEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['auto-sprint'])){
				$result=boolval($data['auto-sprint']);
			}
		}
		return $result;
	}
	public static function isCpsCounterEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['cps-counter'])){
				$result=boolval($data['cps-counter']);
			}
		}
		return $result;
	}
	public static function isCombatCounterEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['combat-counter'])){
				$result=boolval($data['combat-counter']);
			}
		}
		return $result;
	}
	public static function isSwingSoundEnabled($player):bool{
		$result=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['swing-sounds'])){
				$result=boolval($data['swing-sounds']);
			}
		}
		return $result;
	}
	public static function potSplashColor($player){
		$result="default";
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['pot-splash-color'])){
				$result=strval($data['pot-splash-color']);
			}
		}
		return $result;
	}
	public static function particleMod($player){
		$result="off";
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['particle-mod'])){
				$result=strval($data['particle-mod']);
			}
		}
		return $result;
	}
	public static function preferredPot($player){
		$result="default";
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['preferred-pot'])){
				$result=strval($data['preferred-pot']);
			}
		}
		return $result;
	}
	public static function clanTag($player){
		$result="";
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data['clan-tag'])){
				$result=strval($data['clan-tag']);
			}
		}
		return $result;
	}
	public static function sendExtraParticles($player, $hit, int $multiplier){
		switch($multiplier){
			case 1:
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_CRITICAL_HIT;
			$packet->entityRuntimeId=$hit->getId();
			$player->dataPacket($packet);
			break;
			case 2:
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_CRITICAL_HIT;
			$packet->entityRuntimeId=$hit->getId();
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			break;
			case 4:
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_CRITICAL_HIT;
			$packet->entityRuntimeId=$hit->getId();
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			break;
			case 8:
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_CRITICAL_HIT;
			$packet->entityRuntimeId=$hit->getId();
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			$player->dataPacket($packet);
			break;
			default:
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_CRITICAL_HIT;
			$packet->entityRuntimeId=$hit->getId();
			$player->dataPacket($packet);
			break;
		}
	}
	public static function getTags($player){
		return self::getPlayerData(self::getPlayerName($player))['tags'];
	}
	public static function setTags($player, string $value){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['tags'])){
				$tags=$data['tags'];
				$tags[]=$value;
				$data['tags']=$tags;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function getCustomTags($player){
		return self::getPlayerData(self::getPlayerName($player))['custom-tags'];
	}
	public static function setCustomTags($player, string $value){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['custom-tags'])){
				$tags=$data['custom-tags'];
				$tags[]=$value;
				$data['custom-tags']=$tags;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function getTagSlots($player){
		return self::getPlayerData(self::getPlayerName($player))['tag-slots'];
	}
	public static function setTagSlots($player, string $value){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['tag-slots'])){
				$tags=$data['tag-slots'];
				$tags[]=$value;
				$data['tag-slots']=$tags;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function getCapes($player){
		return self::getPlayerData(self::getPlayerName($player))['capes'];
	}
	public static function getSelectedCape($player){
		return self::getPlayerData(self::getPlayerName($player))['selected-cape'];
	}
	public static function setSelectedCape($player, $value):void{
		self::setPlayerData(self::getPlayerName($player), 'selected-cape', $value);
	}
	public static function setCapes($player, string $value){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['capes'])){
				$tags=$data['tags'];
				$tags[]=$value;
				$data['capes']=$tags;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function getKillParticles($player){
		return self::getPlayerData(self::getPlayerName($player))['kill-particles'];
	}
	public static function getSelectedKillParticle($player){
		return self::getPlayerData(self::getPlayerName($player))['kill-particle'];
	}
	public static function setKillParticles($player, string $value){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['kill-particles'])){
				$tags=$data['kill-particles'];
				$tags[]=$value;
				$data['kill-particles']=$tags;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function getRivalries($player){
		return self::getPlayerData(self::getPlayerName($player))['rivalries'];
	}
	public static function startRivalry($player, string $enemy){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['rivalries'])){
				$rivals=$data['rivalries'];
				//$rivals[]=$value;
				$rivals[]=array($enemy => 0);
				$data['rivalries']=$rivals;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function updateRivalry($player, string $enemy){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['rivalries'])){
				$rivals=$data['rivalries'];
				//$rivals[]=$value;
				$current=self::
				$rivals[]=array($enemy => $current + 1);
				$data['rivalries']=$rivals;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function getPerms($player){
		return self::getPlayerData(self::getPlayerName($player))['permissions'];
	}
	public static function clearPerms($player){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['permissions'])){
				$data=array('permissions' => []);
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function setPerms($player, $value){
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(isset($data['permissions'])){
				$perms=$data['permissions'];
				$perms[]=$value;
				$data['permissions']=$perms;
			}
			yaml_emit_file($path, $data);
		}
	}
	public static function setPlayerData($player, string $key, $value=null):bool{
		$executed=true;
		$path=Core::getInstance()->getDataFolder()."playerdata/".self::getPlayerName($player).".yml";
		if(file_exists($path)){
			$data=yaml_parse_file($path, 0);
			if(is_array($data) and isset($data[$key])){
				$data[$key]=$value;
				$executed=true;
			}
			yaml_emit_file($path, $data);
			}else{
				self::initPlayer(self::getPlayer($player));
				$executed=self::setPlayerData($player, $key, $value);
			}
			return $executed;
	}
	public static function getPlayerData($player):array{
		$name=null;
		$data=array();
		if(isset($player) and !is_null($player)){
			if($player instanceof Player){
				$name=$player->getName();
			}else if (is_string($player)){
				$name=$player;
			}
		}
		if(!is_null($name)){
			$path=Core::getInstance()->getDataFolder()."playerdata/".$name.".yml";
			if(file_exists($path)){
				$d=yaml_parse_file($path, 0);
				if(is_array($d)){
					$data=$d;
				}
			}
		}
		return $data;
	}
	public static function setCape($player, string $cape){
		$player=self::getPlayer($player);
		$oldSkin=$player->getSkin();
		$capeData=self::createImage($cape);
		$skin=new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), $capeData, $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
		$player->setSkin($skin);
		$player->sendSkin();
		$player->setHasCape(true);
	}
	public static function removeCape($player){
		$player=self::getPlayer($player);
		$oldSkin=$player->getSkin();
		$skin=new Skin($oldSkin->getSkinId(), $oldSkin->getSkinData(), "", $oldSkin->getGeometryName(), $oldSkin->getGeometryData());
		$player->setSkin($skin);
		$player->sendSkin();
		$player->setHasCape(false);
	}
	public static function customPots(Item $potion, Player $player, bool $animate=false){
		$dir=$player->getDirectionVector();
		$dx=$dir->getX();
		$dz=$dir->getZ();
		$controls=Core::getInstance()->getPlayerControls($player);
		$potion->onClickAir($player, $player->getDirectionVector());
		if(!$player->isCreative()){
			$inventory=$player->getInventory();
			$itemInHand=$player->getInventory()->getItemInHand();
			if($potion->getId()===Item::SPLASH_POTION){
				$inventory->setItem($inventory->getHeldItemIndex(), Item::get(0));
			}
		}
		if($animate===true and $controls=="Touch"){
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_SWING_ARM;
			$packet->entityRuntimeId=$player->getId();
			Core::getInstance()->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $packet);
		}
	}
	public static function generateRandomFloat($min, $max, $round=0){
		if($min>$max){
			$min=$max;
			$max=$min;
			}else{
				$min=$min;
				$max=$max;
		}
		$randomfloat=$min + mt_rand() / mt_getrandmax() * ($max - $min);
		if($round>0){
			$randomfloat=round($randomfloat, $round);
		}
		return $randomfloat;
	}
	public static function giveTemporaryRank($player, $rank){
		switch($rank){
			case "Voter":
			case "voter":
			$temp="Voter";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=0 * 86400;
			$hour=24 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "vip3d":
			$temp="VIP";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=3 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "vip7d":
			$temp="VIP";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=7 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "vip14d":
			$temp="VIP";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=14 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "vip30d":
			$temp="VIP";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=30 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "elite3d":
			$temp="Elite";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=3 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "elite7d":
			$temp="Elite";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=7 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "elite14d":
			$temp="Elite";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=14 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "elite30d":
			$temp="Elite";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=30 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "premium3d":
			$temp="Premium";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=3 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "premium7d":
			$temp="Premium";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=7 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "premium14d":
			$temp="Premium";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=14 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			case "premium30d":
			$temp="Premium";
			$original=Core::getInstance()->getDatabaseHandler()->getRank($player);
			$now=time();
			$day=30 * 86400;
			$hour=0 * 3600;
			$minute=0 * 60;
			$duration=$now + $day + $hour + $minute;
			Core::getInstance()->getDatabaseHandler()->setRank($player, $temp);
			Core::getInstance()->getDatabaseHandler()->temporaryRankCreate($player, $temp, $duration, $original);
			Core::getInstance()->getLogger()->notice($player." has received ".$rank." rank temporarily.");
			break;
			default:
			return;
			break;
		}
	}
	public static function offerVoteRewards($player){
		$now=time();
		$day=0 * 86400;
		$hour=24 * 3600;
		$minute=0 * 60;
		$duration=$now + $day + $hour + $minute;
		$onlineplayer=Server::getInstance()->getPlayer($player);
		if($onlineplayer instanceof Player){
			$onlineplayer->sendMessage("§aThank you for voting ".$onlineplayer->getName().", your rewards have been claimed!");
			self::playSound($onlineplayer, 59, true);
		}
		if(Core::getInstance()->getDatabaseHandler()->getRank($player)=="Player"){
			self::giveTemporaryRank($player, "voter");
		}
		Core::getInstance()->getDatabaseHandler()->voteAccessCreate($player, $duration);
	}
	public static function throwItem(Item $item, $player, bool $animate=false){
		$dir=$player->getDirectionVector();
		$dx=$dir->getX();
		$dz=$dir->getZ();
		$item->onClickAir($player, $player->getDirectionVector());
		if(!$player->isCreative()){
			$inventory=$player->getInventory();
			$itemInHand=$player->getInventory()->getItemInHand();
			if($item->getId()===Item::SPLASH_POTION and $item instanceof DefaultSplashPotion){
				$inventory->setItem($inventory->getHeldItemIndex(), Item::get(0));
			}
			if($item->getId()===Item::ENDER_PEARL and $item instanceof EnderPearl){
				if($itemInHand->getCount() > 0){
					$inventory->removeItem($itemInHand->pop());
				}
				if($itemInHand->getCount()===0){
					$inventory->setItem($inventory->getHeldItemIndex(), Item::get(0));
				}
			}
		}
		if($animate===true){
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_SWING_ARM;
			$packet->entityRuntimeId=$player->getId();
			Core::getInstance()->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $packet);
		}
	}
	public static function consumeItem(Item $item, $player){
		$item->onClickAir($player, $player->getDirectionVector());
		if(!$player->isCreative()){
			$inventory=$player->getInventory();
			$itemInHand=$player->getInventory()->getItemInHand();
			if($item->getId()===Item::MUSHROOM_STEW and $item instanceof MushroomStew){
				//$inventory->setItem($inventory->getHeldItemIndex(), Item::get(281));
				$inventory->setItem($inventory->getHeldItemIndex(), Item::get(0));
				$player->setHealth($player->getHealth() + 8);
				$player->setFood($player->getMaxFood());
			}
		}
	}
	public static function instantPots($item, $player, bool $animate=false){
		$inventory=$player->getInventory();
		if($item===Item::SPLASH_POTION){
			//$inventory->setItem($inventory->getHeldItemIndex(), Item::get(0));
			$player->setHealth($player->getHealth() + 8);
			
			$colors=[new Color(0xf8, 0x24, 0x23)];
			$player->getLevel()->broadcastLevelEvent($player->asVector3()->add($player->getDirectionVector()->x + 0.3, 1, 0), LevelEventPacket::EVENT_PARTICLE_SPLASH, Color::mix(...$colors)->toARGB());
			$player->getLevel()->broadcastLevelSoundEvent($player->asVector3(), LevelSoundEventPacket::SOUND_GLASS);
		}
		if($animate===true){
			$packet=new AnimatePacket();
			$packet->action=AnimatePacket::ACTION_SWING_ARM;
			$packet->entityRuntimeId=$player->getId();
			Core::getInstance()->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $packet);
		}
	}
	public static function spawnParticle(Player $player, $particle, bool $ispreview=false){
		switch($particle){
			case 1:
			if($ispreview===true){
				$players=[$player];
				$player->getlevel()->addParticle(new ExplodeParticle($player->asVector3()), $players);
				$player->getlevel()->addParticle(new ExplodeParticle($player->asVector3()->add(1, 0, 0)), $players);
				$player->getlevel()->addParticle(new ExplodeParticle($player->asVector3()->add(-1, 0, 0)), $players);
				$player->getlevel()->addParticle(new ExplodeParticle($player->asVector3()->add(0, 1, 0)), $players);
				$player->getlevel()->addParticle(new ExplodeParticle($player->asVector3()->add(0 , 0, 1)), $players);
				$player->getlevel()->addParticle(new ExplodeParticle($player->asVector3()->add(0 , 0, -1)), $players);
			}else{
				$player->getlevel()->addParticle(new HugeExplodeParticle($player->asVector3()));
				$player->getlevel()->addParticle(new HugeExplodeParticle($player->asVector3()->add(1, 0, 0)));
				$player->getlevel()->addParticle(new HugeExplodeParticle($player->asVector3()->add(-1, 0, 0)));
				$player->getlevel()->addParticle(new HugeExplodeParticle($player->asVector3()->add(0, 1, 0)));
				$player->getlevel()->addParticle(new HugeExplodeParticle($player->asVector3()->add(0 , 0, 1)));
				$player->getlevel()->addParticle(new HugeExplodeParticle($player->asVector3()->add(0 , 0, -1)));
			}
			break;
			case 2:
			$player->getLevel()->addParticle(new DustParticle($player->asVector3, 255, 10, 10), [$player]);
			default:
			return;
		}
	}
	public static function matchOutcome($player, int $reason, bool $isRanked=false){
		switch($reason){
			case 0:
			$oplayer=self::getPlayer($player);
			$elo=Core::getInstance()->getDatabaseHandler()->getRankedElo($player);
			$wins=Core::getInstance()->getDatabaseHandler()->getWins($player);
			$elogained=Core::getInstance()->getDatabaseHandler()->getEloGained($player);
			$kills=Core::getInstance()->getDatabaseHandler()->getKills($player);
			$dailykills=Core::getInstance()->getDatabaseHandler()->getDailyKills($player);
			$killstreak=Core::getInstance()->getDatabaseHandler()->getKillstreak($player);
			Core::getInstance()->getDatabaseHandler()->setKills($player, $kills+ 1);
			Core::getInstance()->getDatabaseHandler()->setDailyKills($player, $dailykills + 1);
			Core::getInstance()->getDatabaseHandler()->setKillstreak($player, $killstreak + 1);
			$bestkillstreak=Core::getInstance()->getDatabaseHandler()->getBestKillstreak($player);
			$newkillstreak=Core::getInstance()->getDatabaseHandler()->getKillstreak($player);
			if($newkillstreak >= $bestkillstreak){
				Core::getInstance()->getDatabaseHandler()->setBestKillstreak($player, $newkillstreak);
			}
			if(!is_null($oplayer)) $oplayer->sendMessage("§l§eKillStreak §7» §r§a".$newkillstreak);
			$setelo=mt_rand(8, 14);
			if($isRanked===true){
				Core::getInstance()->getDatabaseHandler()->setRankedElo($player, $elo + $setelo);
				Core::getInstance()->getDatabaseHandler()->setWins($player, $wins + 1);
				Core::getInstance()->getDatabaseHandler()->setEloGained($player, $elogained + $setelo);
				if(!is_null($oplayer)) $oplayer->sendMessage("§l§dElo §7» §6Earned: §a+".$setelo);
			}
			break;
			case 1:
			$oplayer=self::getPlayer($player);
			$elo=Core::getInstance()->getDatabaseHandler()->getRankedElo($player);
			$losses=Core::getInstance()->getDatabaseHandler()->getLosses($player);
			$elolost=Core::getInstance()->getDatabaseHandler()->getEloLost($player);
			$deaths=Core::getInstance()->getDatabaseHandler()->getDeaths($player);
			$dailydeaths=Core::getInstance()->getDatabaseHandler()->getDailyDeaths($player);
			$killstreak=Core::getInstance()->getDatabaseHandler()->getKillstreak($player);
			Core::getInstance()->getDatabaseHandler()->setDeaths($player, $deaths + 1);
			Core::getInstance()->getDatabaseHandler()->setDailyDeaths($player, $dailydeaths + 1);
			Core::getInstance()->getDatabaseHandler()->setKillstreak($player, 0);
			if(!is_null($oplayer) and $killstreak > 0) $oplayer->sendMessage("§l§eKillStreak §7» §r§c".$killstreak);
			$setelo=mt_rand(6, 12);
			if($isRanked===true){
				Core::getInstance()->getDatabaseHandler()->setRankedElo($player, $elo - $setelo);
				Core::getInstance()->getDatabaseHandler()->setLosses($player, $losses + 1);
				Core::getInstance()->getDatabaseHandler()->setEloLost($player, $elolost + $setelo);
				if(!is_null($oplayer)) $oplayer->sendMessage("§l§dElo §7» §6Lost: §c-".$setelo);
			}
			$updatedelo=Core::getInstance()->getDatabaseHandler()->getRankedElo($player);
			if(0 > $updatedelo){
				Core::getInstance()->getDatabaseHandler()->setRankedElo($player, 0);
			}
			break;
			default:
			return;
		}
	}
	public static function updateStats($player, int $reason){
		switch($reason){
			case 0:
			$oplayer=self::getPlayer($player);
			$kills=Core::getInstance()->getDatabaseHandler()->getKills($player);
			$dailykills=Core::getInstance()->getDatabaseHandler()->getDailyKills($player);
			$killstreak=Core::getInstance()->getDatabaseHandler()->getKillstreak($player);
			Core::getInstance()->getDatabaseHandler()->setKills($player, $kills+ 1);
			Core::getInstance()->getDatabaseHandler()->setDailyKills($player, $dailykills + 1);
			Core::getInstance()->getDatabaseHandler()->setKillstreak($player, $killstreak + 1);
			$bestkillstreak=Core::getInstance()->getDatabaseHandler()->getBestKillstreak($player);
			$newkillstreak=Core::getInstance()->getDatabaseHandler()->getKillstreak($player);
			if($newkillstreak >= $bestkillstreak){
				Core::getInstance()->getDatabaseHandler()->setBestKillstreak($player, $newkillstreak);
			}
			if(!is_null($oplayer)) $oplayer->sendMessage("§l§eKillStreak §l§7»§r §a".$newkillstreak);
			break;
			case 1:
			$oplayer=self::getPlayer($player);
			$deaths=Core::getInstance()->getDatabaseHandler()->getDeaths($player);
			$dailydeaths=Core::getInstance()->getDatabaseHandler()->getDailyDeaths($player);
			$killstreak=Core::getInstance()->getDatabaseHandler()->getKillstreak($player);
			Core::getInstance()->getDatabaseHandler()->setDeaths($player, $deaths + 1);
			Core::getInstance()->getDatabaseHandler()->setDailyDeaths($player, $dailydeaths + 1);
			Core::getInstance()->getDatabaseHandler()->setKillstreak($player, 0);
			if(!is_null($oplayer) and $killstreak > 0) $oplayer->sendMessage("§l§eKillStreak §r§7»§r §c".$killstreak);
			break;
			case 2:
			$deaths=Core::getInstance()->getDatabaseHandler()->getDeaths($player);
			$dailydeaths=Core::getInstance()->getDatabaseHandler()->getDailyDeaths($player);
			Core::getInstance()->getDatabaseHandler()->setDeaths($player, $deaths + 1);
			Core::getInstance()->getDatabaseHandler()->setDailyDeaths($player, $dailydeaths + 1);
			Core::getInstance()->getDatabaseHandler()->setKillstreak($player, 0);
			break;
			default:
			return;
		}
	}
	public static function isPlayer($player):bool{
		return !is_null(self::getPlayer($player));
	}
	public static function getPlayer($info){
		$result=null;
		$player=self::getPlayerName($info);
		if($player===null){
			return $result;
			return;
		}
		$player=Server::getInstance()->getPlayer($player);
		if($player instanceof Player){
			$result=$player;
		}
		return $result;
	}
	public static function getPlayerName($player){
		$result=null;
		if(isset($player) and !is_null($player)){
			if($player instanceof Player){
				$result=$player->getName();
			}elseif(is_string($player)){
				$result=$player;
			}
		}
		return $result;
	}
	public static function getPlayerDisplayName($player){
		$result=null;
		if(isset($player) and !is_null($player)){
			if($player instanceof Player){
				$result=$player->getDisplayName();
			}elseif(is_string($player)){
				$p=self::getPlayer($player);
				if(!is_null($p)){
					$result=self::getPlayerDisplayName($p);
				}
			}
		}
		return $result;
	}
	public static function spawnStaticTextsToPlayer($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		foreach(Core::getInstance()->staticFloatingTexts as $id => $ft){
			$title=Core::getInstance()->getStaticFloatingTexts()->getNested("$id.title");
			$text=Core::getInstance()->getStaticFloatingTexts()->getNested("$id.text");
			$level=Core::getInstance()->getServer()->getLevelByName(Core::getInstance()->getStaticFloatingTexts()->getNested("$id.level"));
			$ft->setTitle(Core::getInstance()->replaceProcess($player, $title));
			$ft->setText(Core::getInstance()->replaceProcess($player, $text));
			$level->addParticle($ft, [$player]);
		}
	}
	public static function spawnUpdatingTextsToPlayer($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		foreach(Core::getInstance()->updatingFloatingTexts as $id => $ft){
			$title=Core::getInstance()->getUpdatingFloatingTexts()->getNested("$id.title");
			$text=Core::getInstance()->getUpdatingFloatingTexts()->getNested("$id.text");
			$level=Core::getInstance()->getServer()->getLevelByName(Core::getInstance()->getUpdatingFloatingTexts()->getNested("$id.level"));
			$ft->setTitle(Core::getInstance()->replaceProcess($player, $title));
			$ft->setText(Core::getInstance()->replaceProcess($player, $text));
			$level->addParticle($ft, [$player]);
		}
	}
	public static function getTime():string{
		$timezone='America/Chicago';
		$timestamp=time();
		$date=new \DateTime("now", new \DateTimeZone($timezone));
		$date->setTimestamp($timestamp);
		$format=$date->format('m/d/Y');
		return $format;
	}
	public static function getTimeExact():string{
		$timezone='America/Chicago';
		$timestamp=time();
		$date=new \DateTime("now", new \DateTimeZone($timezone));
		$date->setTimestamp($timestamp);
		$format=$date->format('m/d/Y @H:i:s');
		return $format;
	}
	public static function getTimeByHour():string{
		$timezone='America/Chicago';
		$timestamp=time();
		$date=new \DateTime("now", new \DateTimeZone($timezone));
		$date->setTimestamp($timestamp);
		$format=$date->format('H:i');
		return $format;
	}
	public static function getFakeNames(){
		$names=["Trapzies","ghxsty","LuckyXTapz","obeseGamerGirl","UnknownXzzz","zAnthonyyy","FannityPE","Vatitelc","StudSport","MCCaffier","Keepuphulk8181","LittleComfy","Decdarle","mythic_d4nger","gambling life","BASIC x VIBES","lawlogic","hutteric","BiggerCobra_1181","Lextech817717","Chnixxor","AloneShun","AddictedToYou","Board","Javail","MusicPqt","REYESOOKIE","Asaurus Rex","Popperrr","oopsimSorry_","lessthan greaterthan","Regrexxx","adam 22","NotCqnadian","brtineyMCPE","samanthaplayzmc","ShaniquaLOL","OptimusPrimeXD","BouttaBust","GamingNut66","NoIdkbruh","ThisIsWhyYoure___","voLT_811","Sekrum","Artificial_","ReadMyBook","urmum__77","idkwhatiatetoday","udkA77161","Stimpy","Adviser","St1pmyPVP","GangGangGg","CoolKid888","AcornChaser78109","anon171717","AnonymousYT","Sintress Balline","Daviecrusha","HeatedBot46","CobraKiller2828","KingPVPYT","TempestG","ThePVPGod","McProGangYT","lmaonocap","NoClipXD","ImHqcking","undercoverbot","reswoownss199q","diego91881","CindyPlayz","HeyItzMe","iTzSkittlesMC","NOHACKJUSTPRO","idkHowToPlay","Bum Bummm","Bigumslol","Skilumsszz","SuperGamer756","ProPVPer2k20","N0S3_P1CK3R84","PhoenixXD","EnderProYT_81919","Ft MePro","NotHaqing","aababah_a","badbtch4life","serumxxx","bigdogoo_","william18187","ZeroLxck","Gamer dan","SuperSAIN","DefNoHax","GoldFox","ClxpKxng","AdamIsPro","XXXPRO655","proshtGGxD","T0PL543","GamerKid9000","SphericalAxeum","ImABot"];
		return $names;
	}
	public static function abc123($string){
		if(function_exists('ctype_alnum')){
			$return=ctype_alnum($string);
		}else{
			$return=preg_match('[a-z0-9]', $string) > 0;
		}
		return $return;
	}
	public static function isLetter($string){
		if(function_exists('ctype_alnum')){
			$return=ctype_alnum($string);
		}else{
			$return=preg_match('[a-z]', $string) > 0;
		}
		return $return;
	}
	public static function isNumerical($string){
		if(function_exists('ctype_alnum')){
			$return=ctype_alnum($string);
		}else{
			$return=preg_match('[0-9]', $string) > 0;
		}
		return $return;
	}
	public static function replaceVars($str, array $vars){
		foreach($vars as $key => $value){
			$str=str_replace($key, $value, $str);
		}
		return $str;
	}
	public static function postWebhook(String $url, String $content, String $replyTo='Pandaz'){
		$post=new DiscordTask($url, $content, $replyTo);
		$task=Server::getInstance()->getAsyncPool()->submitTask($post);
		return;
	}
	public static function classChangeEvent($player, string $change, string $class){
		$player=self::getPlayer($player);
		switch($change){
			case "promoted":
			$player->broadcastEntityEvent(ActorEventPacket::CONSUME_TOTEM);
			$sound=new LevelEventPacket();
			$sound->evid=1052;
			$sound->position=$player->asVector3()->add(0, $player->eyeHeight, 0);
			$sound->data=1052;
			$player->dataPacket($sound);
			$color=self::getClassColor($class);
			$player->sendMessage("§aYou advanced to a new class, you're now ".$color."§l".strtoupper($class)."§r§a.");
			break;
			case "demoted":
			$color=self::getClassColor($class);
			$player->sendMessage("§cYou went down a class, you're now ".$color."§l".strtoupper($class)."§r§c.");
			break;
			default:
			return;
		}
	}
	public static function spawnLightning($player){
		if($player instanceof Player){
			$player=self::getPlayer($player);
		}else{
			$player=$player;
		}
		if(is_null($player)) return;
		$lightning=new AddActorPacket();
		$lightning->type="minecraft:lightning_bolt";
		$lightning->entityRuntimeId=Entity::$entityCount++;
		$lightning->metadata=[];
		$lightning->motion=null;
		$lightning->yaw=$player->getYaw();
		$lightning->pitch=$player->getPitch();
		$lightning->position=new Vector3($player->getX(), $player->getY(), $player->getZ());
		Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $lightning);
		
		
		self::impactSound($player);
	}
	public static function knockbackPlayer($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$level=$player->getLevel();
		$x=$player->getX();
		$y=$player->getY();
		$z=$player->getZ();
		$dir=$player->getDirectionVector();
		$dx=$dir->getX();
		$dz=$dir->getZ();
		$player->knockBack($player, 0, $dx, $dz);
	}
	public static function impactSound($player){
		if($player instanceof Player){
			$player=self::getPlayer($player);
		}else{
			$player=$player;
		}
		if(is_null($player)) return;
		$sound=new PlaySoundPacket();
		$sound->soundName="ambient.weather.lightning.impact";
		$sound->x=$player->getX();
		$sound->y=$player->getY();
		$sound->z=$player->getZ();
		$sound->volume=1;
		$sound->pitch=1;
		Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $sound);
	}
	public static function teleportSound($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$sound=new PlaySoundPacket();
		$sound->soundName="mob.endermen.portal";
		$sound->x=$player->getX();
		$sound->y=$player->getY();
		$sound->z=$player->getZ();
		$sound->volume=10;
		$sound->pitch=1;
		foreach($player->getLevel()->getPlayers() as $players){
			$players->dataPacket($sound);
		}
	}
	public static function harpSound($player, $pitch){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$sound=new PlaySoundPacket();
		$sound->soundName="note.harp";
		$sound->x=$player->getX();
		$sound->y=$player->getY();
		$sound->z=$player->getZ();
		$sound->volume=1;
		$sound->pitch=$pitch;
		foreach($player->getLevel()->getPlayers() as $players){
			$players->dataPacket($sound);
		}
	}
	public static function clickSound($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$v3=$player->asVector3();
		if(!$v3 instanceof Vector3){
			return;
		}
		$sound=new LevelEventPacket();
		$sound->evid=1001;
		$sound->position=$v3;
		$sound->data=1001;
		$player->dataPacket($sound);
	}
	public static function tesstttSound($player, $id){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$v3=$player->asVector3();
		if(!$v3 instanceof Vector3){
			return;
		}
		$sound=new LevelEventPacket();
		$sound->evid=$id;
		$sound->position=$v3;
		$sound->data=$id;
		$player->dataPacket($sound);
	}
	public static function shootSound($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$v3=$player->asVector3();
		if(!$v3 instanceof Vector3){
			return;
		}
		$sound=new LevelEventPacket();
		$sound->evid=1009;
		$sound->position=$v3;
		$sound->data=1009;
		$player->dataPacket($sound);
	}
	public static function witherSpawnSound($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$sound=new PlaySoundPacket();
		$sound->soundName="mob.wither.spawn";
		$sound->x=$player->getX();
		$sound->y=$player->getY();
		$sound->z=$player->getZ();
		$sound->volume=0.1;
		$sound->pitch=1;
		$player->dataPacket($sound);
	}
	public static function triHitSound($player){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$sound=new PlaySoundPacket();
		$sound->soundName="item.trident.hit";
		$sound->x=$player->getX();
		$sound->y=$player->getY();
		$sound->z=$player->getZ();
		$sound->volume=0.7;
		$sound->pitch=1;
		foreach($player->getLevel()->getPlayers() as $players){
			//if($players==$player and $player!==null){
				$players->dataPacket($sound);
			//}
		}
	}
	public static function throwPlayerBack($player){
		$player=self::getPlayer($player);
		$dir=$player->getDirectionVector();
		$dx=$dir->getX();
		$dz=$dir->getZ();
		$player->knockBack($player, 0, -$dx, -$dz, 0.4);
		self::playSound($player, 84);
	}
	public static function playSound($player, int $sound, $all=false){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$v3=$player->asVector3();
		if(!$v3 instanceof Vector3) return;
		$packet=new LevelSoundEventPacket();
		$packet->position=$v3;
		$packet->sound=$sound;
		if($all===true){
			foreach($player->getLevel()->getPlayers() as $players){
				$players->dataPacket($packet);
			}
		}else{
			$player->dataPacket($packet);
		}
	}
	public static function playSoundAbove($player, int $sound, $all=false){
		$player=self::getPlayer($player);
		if(is_null($player)) return;
		$v3=$player->asVector3()->add(0, 4, 0);
		if(!$v3 instanceof Vector3){
			return;
		}
		$packet=new LevelSoundEventPacket();
		$packet->position=$v3;
		$packet->sound=$sound;
		if($all===true){
			foreach($player->getLevel()->getPlayers() as $players){
				$players->dataPacket($packet);
			}
		}else{
			$player->dataPacket($packet);
		}
	}
	public static function getChatFormat($rank){
		switch($rank){
			case "Player":
			$format="§7{clan}§l§ePlayer §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Voter":
			$format="§7{clan}§l§6Voter §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Elite":
			$format="§7{clan}§l§aElite §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Premium":
			$format="§7{clan}§l§bPremium §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Booster":
			$format="§7{clan}§l§5Booster §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "YouTube":
			$format="§7{clan}§l§cYou§fTube §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Famous":
			$format="§7{clan}§l§dFamous §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Trainee":
			$format="§7{clan}§l§2Trainee §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Helper":
			$format="§7{clan}§l§1Helper §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Mod":
			$format="§7{clan}§l§9Mod §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "HeadMod":
			$format="§7{clan}§l§3Head-Mod §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Admin":
			$format="§7{clan}§l§6Admin §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Manager":
			$format="§7{clan}§l§cManager §r§f{name}§7 » §b{message}";
			return $format;
			break;
			case "Owner":
			$format="§7{clan}§l§4Owner §r§f{name}§7 » §b{message}";
			return $format;
			break;
			default:
			$format="§7{clan}§l§ePlayer §r§f{name}§7 » §b{message}";
			return $format;
			break;
		}
	}
	public static function getNameTagFormat($rank){
		switch($rank){
			case "Player":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Voter":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Elite":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Premium":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Booster":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "YouTube":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Famous":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Trainee":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Helper":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Mod":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "HeadMod":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Admin":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Manager":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Owner":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			case "Founder":
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
			default:
			$format="§f(§a{kills}§f) §f{name}\n§r§f[ §r§bCPS: §r§f{cps} §r§7- §r§b{ping} ms§f ]\n§r§f[ §r§b{os} §f]";
			return $format;
			break;
		}
	}
	public static function createImage($file){
		$path=Core::getInstance()->toGetFile($file);
		$img=@imagecreatefrompng($path);
		$bytes='';
		$l=(int)@getimagesize($path)[1];
		for($y=0; $y < $l; $y++){
			for($x=0; $x < 64; $x++){
				$rgba=@imagecolorat($img, $x, $y);
				$a=((~((int)($rgba >> 24))) << 1) & 0xff;
				$r=($rgba >> 16) & 0xff;
				$g=($rgba >> 8) & 0xff;
				$b=$rgba & 0xff;
				$bytes .= chr($r).chr($g).chr($b).chr($a);
			}
		}
		@imagedestroy($img);
		return $bytes;
	}
	public static function hasPermission($player, $permission){
		$base="";
		$nodes=explode(".", $permission);
		foreach($nodes as $key => $node){
			$seperator=$key == 0 ? "":".";
			$base="$base$seperator$node";
			if($player->hasPermission($base)){
				return true;
			}
		}
		return false;
    }
	public static function translateColors($string){
		$message=preg_replace_callback("/(\\\&|\&)[0-9a-fk-or]/", function($matches){
		return str_replace("§r", "§r§f", str_replace("\\§", "&", str_replace("&", "§", $matches[0])));
		}, $string);
		return $message;
	}
	public static function equals_string(string $input, string...$tests):bool{
		$result=false;
		foreach($tests as $test){
			if($test===$input){
				$result=true;
				break;
			}
		}
		return $result;
	}
	public static function arr_contains_keys(array $haystack, ...$needles):bool{
		$result=true;
		foreach($needles as $key){
			if(!isset($haystack[$key])){
				$result=false;
				break;
			}
		}
		return $result;
	}
	public static function getPositionFromMap($posArr, $level){
		$result=null;
		if(!is_null($posArr) and is_array($posArr) and self::arr_contains_keys($posArr,'x', 'y', 'z')){
			$x=floatval(intval($posArr['x']));
			$y=floatval(intval($posArr['y']));
			$z=floatval(intval($posArr['z']));
			if(self::isALevel($level)){
				$server=Server::getInstance();
				if(self::arr_contains_keys($posArr, 'yaw', 'pitch')){
					$yaw=floatval(intval($posArr['yaw']));
					$pitch=floatval(intval($posArr['pitch']));
					$result=new Location($x, $y, $z, $yaw, $pitch, $server->getLevelByName($level));
				}else{
					$result=new Position($x, $y, $z, $server->getLevelByName($level));
				}
			}
		}
		return $result;
	}
	public static function isALevel($level, bool $loaded=true):bool{
		$server=Server::getInstance();
		$lvl=($level instanceof Level) ? $level:$server->getLevelByName($level);
		$result=null;
		if(is_string($level) and $loaded===false){
			$levels=self::getLevelsFromFolder();
			if(in_array($level, $levels)){
				$result=true;
			}
		} elseif($lvl instanceof Level){
			$name=$lvl->getName();
			if($loaded===true){
				$result=$server->isLevelLoaded($name);
			}
		}
		return $result;
	}
	public static function getLevelsFromFolder(){
		$index=self::str_indexOf("/plugin_data", Core::getInstance()->getDataFolder());
		$substr=substr(Core::getInstance()->getDataFolder(), 0, $index);
		$worlds=$substr . "/worlds";
		if(!is_dir($worlds)){
			return [];
		}
		$files=scandir($worlds);
		return $files;
	}
	public static function str_indexOf(string $needle, string $haystack, int $len=0):int{
		$result=-1;
		$indexes=self::str_indexes($needle, $haystack);
		$length=count($indexes);
		if($length > 0){
			$length=$length - 1;
			$indexOfArr=($len > $length or $len < 0 ? 0:$len);
			$result=$indexes[$indexOfArr];
		}
		return $result;
	}
	public static function str_indexes(string $needle, string $haystack):array{
		$result=[];
		$end=strlen($needle);
		$len=0;
		while(($len + $end) <= strlen($haystack)){
			$substr=substr($haystack, $len, $end);
			if($needle===$substr){
				$result[]=$len;
			}
			$len++;
		}
		return $result;
	}
	public static function areLevelsEqual(Level $a, Level $b):bool{
		$aName=$a->getName();
		$bName=$b->getName();
		return $aName===$bName;
	}
}