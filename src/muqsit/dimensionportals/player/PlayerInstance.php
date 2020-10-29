<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\event\player\PlayerEnterPortalEvent;
use muqsit\dimensionportals\event\player\PlayerPortalTeleportEvent;
use muqsit\dimensionportals\exoblock\PortalExoBlock;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\player\Player;

final class PlayerInstance{

	/** @var Player */
	private $player;

	/** @var PlayerPortalInfo|null */
	private $in_portal;

	/** @var bool */
	private $changing_dimension = false;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function onEnterPortal(PortalExoBlock $block) : void{
		($ev = new PlayerEnterPortalEvent($this->player, $block, $this->player->isCreative() ? 0 : $block->getTeleportationDuration()))->call();
		if(!$ev->isCancelled()){
			$this->in_portal = new PlayerPortalInfo($block, $ev->getTeleportDuration());
			PlayerManager::scheduleTicking($this->player);
		}
	}

	public function onLeavePortal() : void{
		PlayerManager::stopTicking($this->player);
		$this->in_portal = null;
	}

	public function onBeginDimensionChange(int $network_dimension_id, Vector3 $position, bool $respawn) : void{
		$session = $this->player->getNetworkSession();

		$this->changing_dimension = true;
		$packet = new ChangeDimensionPacket();
		$packet->dimension = $network_dimension_id;
		$packet->position = $position;
		$packet->respawn = $respawn;
		$session->sendDataPacket($packet);
	}

	public function onEndDimensionChange() : void{
		$this->changing_dimension = false;
		$this->player->getNetworkSession()->sendDataPacket(PlayStatusPacket::create(PlayStatusPacket::PLAYER_SPAWN));
	}

	/**
	 * Returns whether the player is on the dimension
	 * changing screen.
	 *
	 * @return bool
	 */
	public function isChangingDimension() : bool{
		return $this->changing_dimension;
	}

	public function tick() : void{
		if($this->in_portal->tick()){
			$this->teleport();
			$this->onLeavePortal();
		}
	}

	private function teleport() : void{
		$to = $this->in_portal->getBlock()->getTargetWorldInstance();
		$target = Location::fromObject((WorldManager::get($this->player->getWorld()) === $to ? WorldManager::getOverworld() : $to)->getWorld()->getSpawnLocation());
		($ev = new PlayerPortalTeleportEvent($this->player, $this->in_portal->getBlock(), $target))->call();
		if(!$ev->isCancelled()){
			$this->player->teleport($ev->getTarget());
		}
	}
}