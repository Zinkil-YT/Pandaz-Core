<?php

declare(strict_types=1);

namespace Zinkil\pc\tasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use Zinkil\pc\discord\Message;
use Zinkil\pc\discord\Webhook;

class DiscordTask extends AsyncTask{
	
	protected $webhook;
	protected $message;
	
	public function __construct(Webhook $webhook, Message $message){
		$this->webhook=$webhook;
		$this->message=$message;
	}
	public function onRun(){
		$web=curl_init($this->webhook->getURL());
		curl_setopt($web, CURLOPT_POSTFIELDS, json_encode($this->message));
		curl_setopt($web, CURLOPT_POST,true);
		curl_setopt($web, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($web, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($web, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($web, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
		$this->setResult(curl_exec($web));
		curl_close($web);
	}
	public function onCompletion(Server $server){
		$response=$this->getResult();
		if($response!==""){
			Server::getInstance()->getLogger()->error("[Discord] Got error: " . $response);
		}
	}
}