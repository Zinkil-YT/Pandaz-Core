<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\Task;
use Zinkil\pc\Core;
use Zinkil\pc\Utils;
use Zinkil\pc\discord\{Webhook, Message, Embed};

class StatusTask extends Task{
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
		$this->line=-1;
	}
	public function onRun(int $tick):void{
		$webHook=new Webhook(Core::WEBHOOK);
		$emessage=new Message();
		//$emessage->setContent("New logged event.");
		$embed=new Embed();
		$embed->setTitle("Server Status");
		$embed->setColor(0x00F6FF);
		$embed->addField("Playing", count($this->plugin->getServer()->getOnlinePlayers())."/".$this->plugin->getServer()->getMaxPlayers());
		//$embed->setDescription($damager->getName());
		$embed->setFooter("Query Time: ".Utils::getTimeExact(), null);
		$emessage->addEmbed($embed);
		$webHook->send($emessage);
	}
}