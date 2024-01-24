<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use Logger;
use muqsit\dimensionportals\event\player\PlayerEnterPortalEvent;
use muqsit\dimensionportals\event\player\PlayerPortalTeleportEvent;
use muqsit\dimensionportals\exoblock\PortalExoBlock;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;
use PrefixedLogger;
use ReflectionProperty;

final class PlayerInstance{

	readonly public Player $player;
	readonly public Logger $logger;
	readonly private ReflectionProperty $_chunksPerTick;
	private int $chunks_per_tick_before_change;

	private ?PlayerPortalInfo $in_portal = null;
	private bool $changing_dimension = false;

	public function __construct(Player $player, Logger $logger){
		$this->player = $player;
		$this->logger = new PrefixedLogger($logger, $player->getName());

		static $_chunksPerTick = null;
		$_chunksPerTick ??= new ReflectionProperty(Player::class, "chunksPerTick");
		$this->_chunksPerTick = $_chunksPerTick;
	}

	public function onEnterPortal(PortalExoBlock $block, Position $position) : void{
		($ev = new PlayerEnterPortalEvent($this->player, $block, $position, $this->player->isCreative() ? 0 : $block->teleportation_duration))->call();
		if(!$ev->isCancelled()){
			$this->in_portal = new PlayerPortalInfo($block, $ev->teleport_duration);
			PlayerManager::scheduleTicking($this->player);
		}
	}

	public function onLeavePortal() : void{
		PlayerManager::stopTicking($this->player);
		$this->in_portal = null;
	}

	public function onBeginDimensionChange(int $network_dimension_id, Vector3 $position, bool $respawn) : void{
		$session = $this->player->getNetworkSession();
		PlayerManager::$_changing_dimension_sessions[spl_object_id($session)] = true;
		$this->changing_dimension = true;
		$this->chunks_per_tick_before_change = $this->_chunksPerTick->getValue($this->player);
		if($this->chunks_per_tick_before_change < 40){
			$this->_chunksPerTick->setValue($this->player, 40);
		}
		$session->sendDataPacket(ChangeDimensionPacket::create($network_dimension_id, $position, $respawn));
		PlayerManager::$_queue_fast_dimension_change_request[$id = $this->player->getId()] = $id;
		$this->logger->debug("Started changing dimension (network_dimension_id: {$network_dimension_id}, position: {$position->asVector3()}, respawn: " . ($respawn ? "true" : "false") . ")");
	}

	public function onEndDimensionChange() : void{
		$session = $this->player->getNetworkSession();
		unset(PlayerManager::$_changing_dimension_sessions[spl_object_id($session)]);
		$this->changing_dimension = false;
		$this->_chunksPerTick->setValue($this->player, $this->chunks_per_tick_before_change);
		$this->logger->debug("Stopped changing dimension");
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
		$to = $this->in_portal->block->getTargetWorldInstance();
		$world = (WorldManager::get($this->player->getWorld()) === $to ? WorldManager::getOverworld() : $to)->world;
		$target = Location::fromObject($world->getSpawnLocation(), $world, 0.0, 0.0);
		($ev = new PlayerPortalTeleportEvent($this->player, $this->in_portal->block, $target))->call();
		if(!$ev->isCancelled()){
			$this->player->teleport($ev->target);
		}
	}
}