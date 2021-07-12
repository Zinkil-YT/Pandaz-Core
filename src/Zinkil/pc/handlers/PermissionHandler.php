<?php

declare(strict_types=1);

namespace Zinkil\pc\handlers;

use pocketmine\Player;
use pocketmine\Server;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;

class PermissionHandler{
	
	private $plugin;

	public function __construct(){
		$this->plugin=Core::getInstance();
	}
	public function addPermission(Player $player, string $rank){
		switch($rank){
			case "Player":
			return;
			break;
			case "Voter":
			/*$existing=Utils::getPerms($player->getName());
			$permissions=["pc.command.fly"];
			Utils::clearPerms($player->getName());
			foreach($permissions as $perm){
				Utils::setPerms($player->getName(), $perm);
			}*/
			$player->addAttachment($this->plugin, "pc.command.fly", true);
			break;
			case "Elite":
			$player->addAttachment($this->plugin, "pc.command.fly", true);
			break;
			case "Premium":
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.fly", true);
			break;
			case "Booster":
			$player->addAttachment($this->plugin, "pc.command.fly", true);
			break;
			case "YouTube":
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.fly", true);
			break;
			case "Famous":
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.fly", true);
			break;
			case "Builder":
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.gm", true);
			$player->addAttachment($this->plugin, "pc.can.build", true);
			$player->addAttachment($this->plugin, "pc.can.break", true);
			break;
			case "Trainee":
			$player->addAttachment($this->plugin, "pc.command.staff", true);
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.vanish", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.mute", true);
			$player->addAttachment($this->plugin, "pc.command.freeze", true);
			$player->addAttachment($this->plugin, "pc.staff.cheatalerts", true);
			break;
			case "Helper":
			$player->addAttachment($this->plugin, "pc.command.staff", true);
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.vanish", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.mute", true);
			$player->addAttachment($this->plugin, "pc.command.who", true);
			$player->addAttachment($this->plugin, "pc.command.freeze", true);
			$player->addAttachment($this->plugin, "pc.staff.cheatalerts", true);
			$player->addAttachment($this->plugin, "pc.command.alias", true);
			$player->addAttachment($this->plugin, "pocketmine.command.teleport", true);
			break;
			case "Mod":
			$player->addAttachment($this->plugin, "pc.command.alias", true);
			$player->addAttachment($this->plugin, "pc.command.staff", true);
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.online", true);
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.mute", true);
			$player->addAttachment($this->plugin, "pc.command.freeze", true);
			$player->addAttachment($this->plugin, "pc.command.who", true);
			$player->addAttachment($this->plugin, "pc.command.gm", true);
			$player->addAttachment($this->plugin, "pc.command.vanish", true);
			$player->addAttachment($this->plugin, "pc.staff.cheatalerts", true);
			$player->addAttachment($this->plugin, "pocketmine.command.teleport", true);
			$player->addAttachment($this->plugin, "pocketmine.command.kick", true);
			break;
			case "HeadMod":
			$player->addAttachment($this->plugin, "pc.command.alias", true);
			$player->addAttachment($this->plugin, "pc.command.staff", true);
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.online", true);
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.messages", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.online", true);
			$player->addAttachment($this->plugin, "pc.command.mute", true);
			$player->addAttachment($this->plugin, "pc.command.freeze", true);
			$player->addAttachment($this->plugin, "pc.command.who", true);
			$player->addAttachment($this->plugin, "pc.command.gm", true);
			$player->addAttachment($this->plugin, "pocketmine.command.time", true);
			$player->addAttachment($this->plugin, "pc.bypass.vanishsee", true);
			$player->addAttachment($this->plugin, "pc.command.vanish", true);
			$player->addAttachment($this->plugin, "pc.staff.cheatalerts", true);
			$player->addAttachment($this->plugin, "pocketmine.command.teleport", true);
			$player->addAttachment($this->plugin, "pocketmine.command.kick", true);
			break;
			case "Admin":
			$player->addAttachment($this->plugin, "pc.command.alias", true);
			$player->addAttachment($this->plugin, "pc.command.staff", true);
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.online", true);
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.messages", true);
			$player->addAttachment($this->plugin, "pc.command.who", true);
			$player->addAttachment($this->plugin, "pc.command.freeze", true);
			$player->addAttachment($this->plugin, "pc.command.gm", true);
			$player->addAttachment($this->plugin, "pc.command.gmother", true);
			$player->addAttachment($this->plugin, "pc.command.rank", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.mute", true);
			$player->addAttachment($this->plugin, "pc.command.vanish", true);
			$player->addAttachment($this->plugin, "pc.bypass.vanishsee", true);
			$player->addAttachment($this->plugin, "pc.staff.cheatalerts", true);
			$player->addAttachment($this->plugin, "pc.staff.notifications", true);
			$player->addAttachment($this->plugin, "pc.bypass.chatcooldown", true);
			$player->addAttachment($this->plugin, "pc.bypass.chatsilence", true);
			$player->addAttachment($this->plugin, "pc.bypass.combatcommand", true);
			$player->addAttachment($this->plugin, "pocketmine.command.teleport", true);
			$player->addAttachment($this->plugin, "pocketmine.command.give", true);
			$player->addAttachment($this->plugin, "pocketmine.command.kick", true);
			$player->addAttachment($this->plugin, "pocketmine.command.ban", true);
			$player->addAttachment($this->plugin, "pocketmine.command.pardon", true);
			$player->addAttachment($this->plugin, "pocketmine.command.time", true);
			break;
			case "Manager":
			$player->addAttachment($this->plugin, "pc.command.mutechat", true);
			$player->addAttachment($this->plugin, "pc.command.alias", true);
			$player->addAttachment($this->plugin, "pc.command.staff", true);
			$player->addAttachment($this->plugin, "pc.access.staffchat", true);
			$player->addAttachment($this->plugin, "pc.command.online", true);
			$player->addAttachment($this->plugin, "pc.command.disguise", true);
			$player->addAttachment($this->plugin, "pc.command.messages", true);
			$player->addAttachment($this->plugin, "pc.command.who", true);
			$player->addAttachment($this->plugin, "pc.command.announce", true);
			$player->addAttachment($this->plugin, "pc.command.pban", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.mute", true);
			$player->addAttachment($this->plugin, "pc.command.coords", true);
			$player->addAttachment($this->plugin, "pc.command.freeze", true);
			$player->addAttachment($this->plugin, "pc.command.gm", true);
			$player->addAttachment($this->plugin, "pc.command.gmother", true);
			$player->addAttachment($this->plugin, "pc.command.rank", true);
			$player->addAttachment($this->plugin, "pc.command.tban", true);
			$player->addAttachment($this->plugin, "pc.command.vanish", true);
			$player->addAttachment($this->plugin, "pc.bypass.vanishsee", true);
			$player->addAttachment($this->plugin, "pc.staff.cheatalerts", true);
			$player->addAttachment($this->plugin, "pc.staff.notifications", true);
			$player->addAttachment($this->plugin, "pc.bypass.chatcooldown", true);
			$player->addAttachment($this->plugin, "pc.bypass.chatsilence", true);
			$player->addAttachment($this->plugin, "pc.bypass.combatcommand", true);
			$player->addAttachment($this->plugin, "pocketmine.command.teleport", true);
			$player->addAttachment($this->plugin, "pocketmine.command.give", true);
			$player->addAttachment($this->plugin, "pocketmine.command.kick", true);
			$player->addAttachment($this->plugin, "pocketmine.command.ban", true);
			$player->addAttachment($this->plugin, "pocketmine.command.pardon", true);
			$player->addAttachment($this->plugin, "pocketmine.command.time", true);
			break;
			case "Owner":
			return;
			break;
			case "Founder":
			return;
			break;
		}
	}
}