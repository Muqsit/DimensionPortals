<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use Logger;
use muqsit\dimensionportals\event\player\PlayerEnterPortalEvent;
use muqsit\dimensionportals\event\player\PlayerPortalTeleportEvent;
use muqsit\dimensionportals\exoblock\PortalExoBlock;
use muqsit\dimensionportals\WorldManager;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\player\Player;
use pocketmine\world\Position;
use ReflectionProperty;

final class PlayerInstance{

	readonly private ReflectionProperty $_chunksPerTick;
	private int $chunks_per_tick_before_change;

	private ?PlayerPortalInfo $in_portal = null;
	private bool $changing_dimension = false;

	public function __construct(
		readonly public PlayerManager $player_manager,
		readonly public WorldManager $world_manager,
		readonly public Player $player,
		readonly public Logger $logger
	){
		static $_chunksPerTick = null;
		$_chunksPerTick ??= new ReflectionProperty(Player::class, "chunksPerTick");
		$this->_chunksPerTick = $_chunksPerTick;
	}

	public function onEnterPortal(PortalExoBlock $block, Position $position) : void{
		($ev = new PlayerEnterPortalEvent($this->player, $block, $position, $this->player->isCreative() ? 0 : $block->teleportation_duration))->call();
		if(!$ev->isCancelled()){
			$this->in_portal = new PlayerPortalInfo($block, $position, $ev->teleport_duration);
			$this->player_manager->scheduleTicking($this->player);
		}
	}

	public function onLeavePortal() : void{
		$this->player_manager->stopTicking($this->player);
		$this->in_portal = null;
	}

	public function onBeginDimensionChange(int $network_dimension_id, Vector3 $position, bool $respawn) : void{
		$session = $this->player->getNetworkSession();
		$this->player_manager->_changing_dimension_sessions[spl_object_id($session)] = true;
		$this->changing_dimension = true;
		$this->chunks_per_tick_before_change = $this->_chunksPerTick->getValue($this->player);
		if($this->chunks_per_tick_before_change < 40){
			$this->_chunksPerTick->setValue($this->player, 40);
		}
		$session->sendDataPacket(ChangeDimensionPacket::create($network_dimension_id, $position, $respawn, null));
		$this->player_manager->_queue_fast_dimension_change_request[$id = $this->player->getId()] = $id;
		$this->logger->debug("Started changing dimension (network_dimension_id: {$network_dimension_id}, position: {$position->asVector3()}, respawn: " . ($respawn ? "true" : "false") . ")");
	}

	public function onEndDimensionChange() : void{
		$session = $this->player->getNetworkSession();
		unset($this->player_manager->_changing_dimension_sessions[spl_object_id($session)]);
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
		$current_dimension = $this->world_manager->world_dimensions[$this->player->getWorld()->getFolderName()] ?? $this->world_manager->default_dimension;
		$portal_dimension = $this->in_portal->block->dimension;
		$target_dimension = $current_dimension === $portal_dimension ? WorldManager::TYPE_OVERWORLD : $portal_dimension;
		$target_world_name = $this->world_manager->default_worlds[$target_dimension];
		$world = $target_world_name !== null ? $this->world_manager->server_manager->getWorldByName($this->world_manager->default_worlds[$target_world_name]) : null;
		if($world === null){
			return;
		}
		$target = Location::fromObject($world->getSpawnLocation(), $world, 0.0, 0.0);
		($ev = new PlayerPortalTeleportEvent($this->player, $this->in_portal->block, $this->in_portal->block_position, $target))->call();
		if($ev->isCancelled()){
			return;
		}
		$this->player->teleport($ev->target);
	}
}
