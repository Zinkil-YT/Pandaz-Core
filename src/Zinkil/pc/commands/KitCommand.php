<?php

declare(strict_types=1);

namespace Zinkil\pc\Commands;

use pocketmine\Player;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use Zinkil\pc\Core;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Kits;

class KitCommand extends PluginCommand{
	
	private $plugin;
	
	public function __construct(Core $plugin){
		parent::__construct("kit", $plugin);
		$this->plugin=$plugin;
		$this->setDescription("§bRekit command");
	}
	public function execute(CommandSender $player, string $commandLabel, array $args){
		if(!$player instanceof Player){
			return;
		}
		if(!$player->isOp()){
			if($player->isTagged()){
				$player->sendMessage("§cYou cannot use this command while in combat.");
				return;
			}
		}
		if($player->isInParty()){
			$player->sendMessage("§cYou cannot use this command while in a party.");
			return;
		}
		$duel=$this->plugin->getDuelHandler()->getDuelFromSpec($player);
		if(!is_null($duel)){
			$player->sendMessage("§cYou cannot use this command while in spectating a duel.");
			return;
		}
		if($this->plugin->getDuelHandler()->isInDuel($player) or $this->plugin->getDuelHandler()->isInBotDuel($player)){
			$player->sendMessage("§cYou cannot use this command while in a duel.");
			return;
		}
		if($player->getPlayerLocation()===0) Kits::sendKit($player, "lobby");
		if($player->getPlayerLocation()===1) Kits::sendKit($player, "nodebuff");
		if($player->getPlayerLocation()===2) Kits::sendKit($player, "gapple");
		if($player->getPlayerLocation()===3) Kits::sendKit($player, "opgapple");
		if($player->getPlayerLocation()===4) Kits::sendKit($player, "combo");
		if($player->getPlayerLocation()===5) Kits::sendKit($player, "fist");
		if($player->getPlayerLocation()===6) return;
		if($player->getPlayerLocation()===7) Kits::sendKit($player, "nodebuff");
		if($player->getPlayerLocation()===8) Kits::sendKit($player, "nodebuffjava");
		if($player->getPlayerLocation()===9) Kits::sendKit($player, "resistance");
		if($player->getPlayerLocation()===11) Kits::sendKit($player, "sumoffa");
		if($player->getPlayerLocation()===12) Kits::sendKit($player, "knockbackffa");
	}
}