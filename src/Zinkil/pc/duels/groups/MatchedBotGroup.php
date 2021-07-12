<?php

declare(strict_types=1);

namespace Zinkil\pc\duels\groups;

use pocketmine\Player;
use Zinkil\pc\Utils;

class MatchedBotGroup{
	
	private $playerName;
	private $botName="Unknown";
	private $bot;
	private $difficulty;

	public function __construct($player, $bot, string $difficulty){
		$this->playerName=Utils::getPlayerName($player);
		if($bot!==null) $this->botName=$bot->getName();
		$this->difficulty=$difficulty;
		$this->bot=$bot;
	}
	public function getPlayerName():string{
		return $this->playerName;
	}
	public function getBotName():string{
		return $this->botName;
	}
	public function getPlayer(){
		return Utils::getPlayer($this->playerName);
	}
	public function getBot(){
		return $this->bot;
	}
    public function isPlayerOnline(){
        $player=$this->getOpponent();
        return !is_null($player) and $player->isOnline();
    }
    public function isBotOnline(){
        $bot=$this->getBot();
        return !is_null($this->bot) and $this->bot->isAlive();
    }
    public function getDifficulty():string{
    	return $this->difficulty;
   }
    public function equals($object):bool{
        return false;
    }
}