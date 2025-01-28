<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use Logger;
use muqsit\dimensionportals\event\PlayerDimensionScreenChangeEvent;
use muqsit\dimensionportals\event\PlayerPortalEnterEvent;
use muqsit\dimensionportals\event\PlayerPortalTeleportEvent;
use muqsit\dimensionportals\exoblock\BlockManager;
use muqsit\dimensionportals\Utils;
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

	/** @var WorldManager::DIMENSION_*|null */
	private ?int $changing_dimension = null;

	public function __construct(
		readonly public PlayerManager $player_manager,
		readonly public BlockManager $block_manager,
		readonly public WorldManager $world_manager,
		readonly public Player $player,
		readonly public Logger $logger
	){
		static $_chunksPerTick = null;
		$_chunksPerTick ??= new ReflectionProperty(Player::class, "chunksPerTick");
		$this->_chunksPerTick = $_chunksPerTick;
	}

	/**
	 * @param WorldManager::DIMENSION_* $dimension
	 * @param Position $position
	 */
	public function onEnterPortal(int $dimension, Position $position) : void{
		($ev = new PlayerPortalEnterEvent($this->player, $dimension, $position, match(true){
			$this->player->isCreative() => 0,
			$dimension === WorldManager::DIMENSION_NETHER => $this->block_manager->nether_portal_tp_duration,
			$dimension === WorldManager::DIMENSION_END => $this->block_manager->end_portal_tp_duration,
			default => 0
		}))->call();
		if(!$ev->isCancelled()){
			$this->in_portal = new PlayerPortalInfo($dimension, $position, $ev->teleport_duration);
			$this->player_manager->scheduleTicking($this->player);
		}
	}

	public function onLeavePortal() : void{
		$this->player_manager->stopTicking($this->player);
		$this->in_portal = null;
	}

	/**
	 * @param WorldManager::DIMENSION_* $dimension
	 * @param Vector3 $position
	 * @param bool $respawn
	 */
	public function onBeginDimensionChange(int $dimension, Vector3 $position, bool $respawn) : void{
		$session = $this->player->getNetworkSession();
		$this->player_manager->_changing_dimension_sessions[spl_object_id($session)] = true;
		$this->changing_dimension = $dimension;
		$this->chunks_per_tick_before_change = $this->_chunksPerTick->getValue($this->player);
		if($this->chunks_per_tick_before_change < 40){
			$this->_chunksPerTick->setValue($this->player, 40);
		}
		$session->sendDataPacket(ChangeDimensionPacket::create(Utils::coreDimensionToNetwork($dimension), $position, $respawn, null));
		$this->player_manager->_queue_fast_dimension_change_request[$id = $this->player->getId()] = $id;
		$this->logger->debug("Started changing dimension (dimension: {$dimension}, position: {$position->asVector3()}, respawn: " . ($respawn ? "true" : "false") . ")");
		(new PlayerDimensionScreenChangeEvent($this->player, $dimension, PlayerDimensionScreenChangeEvent::STATE_BEGIN))->call();
	}

	public function onEndDimensionChange() : void{
		$dimension = $this->changing_dimension;
		$session = $this->player->getNetworkSession();
		unset($this->player_manager->_changing_dimension_sessions[spl_object_id($session)]);
		$this->changing_dimension = null;
		$this->_chunksPerTick->setValue($this->player, $this->chunks_per_tick_before_change);
		$this->logger->debug("Stopped changing dimension");
		(new PlayerDimensionScreenChangeEvent($this->player, $dimension, PlayerDimensionScreenChangeEvent::STATE_END))->call();
	}

	/**
	 * @return WorldManager::DIMENSION_*|null
	 */
	public function getChangingDimension() : ?int{
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
		$portal_dimension = $this->in_portal->dimension;
		$target_dimension = $current_dimension === $portal_dimension ? WorldManager::DIMENSION_OVERWORLD : $portal_dimension;
		$target_world_name = $this->world_manager->default_worlds[$target_dimension];
		$world = $target_world_name !== null ? $this->world_manager->server_manager->getWorldByName($target_world_name) : null;
		if($world === null){
			return;
		}
		$target = Location::fromObject($world->getSpawnLocation(), $world, 0.0, 0.0);
		($ev = new PlayerPortalTeleportEvent($this->player, $portal_dimension, $this->in_portal->block_position, $target))->call();
		if($ev->isCancelled()){
			return;
		}
		$this->player->teleport($ev->target);
	}
}
