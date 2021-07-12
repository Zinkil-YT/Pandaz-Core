<?php

declare(strict_types=1);

namespace Zinkil\pc\multiver;

use pocketmine\item\FoodSource;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;
use pocketmine\Server
use Zinkil\pc\Utils;
use Zinkil\pc\CPlayer;
use Zinkil\pc\Core;

class PMPlayer extends Player
{

	public $plugin;
	
	public function __construct(Core $plugin){
		$this->plugin=$plugin;
	}

    /** @var string */
    protected $version = ProtocolInfo::MINECRAFT_VERSION_NETWORK;

    public function broadcastMovement(bool $teleport = false): void
    {

        $pk = new MoveActorAbsolutePacket();
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this);

        $pk->xRot = $this->pitch;
        $pk->yRot = $this->yaw;
        $pk->zRot = $this->yaw;

        if($teleport){
            $pk->flags |= MoveActorAbsolutePacket::FLAG_TELEPORT;
        }

        /** @var CPlayer[] $viewers */
        $viewers = $this->getViewers();

        // Broadcasts the packet to 1.14.60 & below players.
        Utils::broadcastPacketToViewers($this, $pk, function(CPlayer $player) {
            return strpos($player->getVersion(), "1.14.60") === false;
        }, $viewers);

        /** @var CPlayer[] $viewers */
        $viewers = array_filter($viewers, function(CPlayer $viewer) {
            return strpos($viewer->getVersion(), "1.14.60") !== false;
        });
            $this->sendPosition($this->asVector3(), $this->yaw, $this->pitch, MovePlayerPacket::MODE_NORMAL, $viewers);
    }

    public function handleEntityEvent(ActorEventPacket $packet): bool
    {
        if (!$this->spawned or !$this->isAlive()) {
            return true;
        }

        $this->doCloseInventory();

        $itemID = $packet->data;
        $entityID = $packet->entityRuntimeId;

        switch ($packet->event) {

            case ActorEventPacket::PLAYER_ADD_XP_LEVELS:

                if ($itemID === 0) {
                    return false;
                }
                $this->dataPacket($packet);
                $this->server->broadcastPacket($this->getViewers(), $packet);
                break;

            case ActorEventPacket::EATING_ITEM:

                if ($itemID === 0 or $entityID !== $this->getId()) {
                    return false;
                }

                $itemInHand = $this->inventory->getItemInHand();
                if ($itemInHand->getId() !== $itemID) {
                    return false;
                } elseif ($itemInHand->getId() === $itemID and !$this->isUsingItem()) {
                    return false;
                }

                if ($itemInHand instanceof FoodSource and $itemInHand->requiresHunger() and !$this->isHungry()) {
                    return false;
                }

                $this->dataPacket($packet);
                $this->server->broadcastPacket($this->getViewers(), $packet);
                break;

            default: return false;
        }

        return true;
    }

    public function handleLogin(LoginPacket $packet): bool
    {

        $this->version = (string)$packet->clientData["GameVersion"];

        return parent::handleLogin($packet);
    }


    /**
     * @return string
     *
     * Gets the game version of the player.
     */
    public function getVersion() : string {
        return $this->version;
    }
}