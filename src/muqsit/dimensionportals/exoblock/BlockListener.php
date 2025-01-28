<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\event\PlayerPortalCreateEvent;
use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\Utils;
use muqsit\dimensionportals\WorldManager;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\EndPortalFrame;
use pocketmine\block\NetherPortal;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;
use SplQueue;
use function assert;

final class BlockListener implements Listener{

	public function __construct(
		readonly public BlockManager $block_manager,
		readonly public PlayerManager $player_manager,
		readonly public WorldManager $world_manager
	){}

	private function meetsNetherPortalSupportConditions(BlockTransaction $transaction, Vector3 $pos) : bool{
		$valid_blocks = [
			$this->block_manager->nether_portal_frame_block->getTypeId() => true,
			$this->block_manager->portal_block_dimensions[WorldManager::DIMENSION_NETHER]->getTypeId() => true
		];
		$faces = [];
		if($pos->y < World::Y_MAX - 1){
			$faces[] = Facing::UP;
		}
		if($pos->y > World::Y_MIN){
			$faces[] = Facing::DOWN;
		}
		$portal_block = $transaction->fetchBlockAt($pos->x, $pos->y, $pos->z);
		if($portal_block instanceof NetherPortal){
			$axis = $portal_block->getAxis();
		}else{
			$axis = Axis::Z;
		}
		if($axis === Axis::Z){
			$faces[] = Facing::SOUTH;
			$faces[] = Facing::NORTH;
		}else{
			assert($axis === Axis::X);
			$faces[] = Facing::WEST;
			$faces[] = Facing::EAST;
		}
		foreach($faces as $face){
			$side_pos = $pos->getSide($face);
			$block = $transaction->fetchBlockAt($side_pos->x, $side_pos->y, $side_pos->z);
			if(!isset($valid_blocks[$block->getTypeId()])){
				return false;
			}
		}
		return true;
	}

	private function meetsEndPortalSupportConditions(BlockTransaction $transaction, Vector3 $pos) : bool{
		$valid_blocks = [
			$this->block_manager->end_portal_frame_activated_block->getTypeId() => true,
			$this->block_manager->end_portal_frame_unactivated_block->getTypeId() => true,
			$this->block_manager->portal_block_dimensions[WorldManager::DIMENSION_END]->getTypeId() => true
		];
		foreach(Facing::HORIZONTAL as $side){
			$side_pos = $pos->getSide($side);
			$type_id = $transaction->fetchBlockAt($side_pos->x, $side_pos->y, $side_pos->z)->getTypeId();
			if(!isset($valid_blocks[$type_id])){
				return false;
			}
		}
		return true;
	}

	/**
	 * @param World $world
	 * @param Vector3 $origin
	 * @param Axis::X|Axis::Z $axis
	 * @param list<Block>|null $frame_blocks
	 * @param-out list<Block> $frame_blocks
	 * @return BlockTransaction|null
	 */
	private function fillNetherPortal(World $world, Vector3 $origin, int $axis, ?array &$frame_blocks) : ?BlockTransaction{
		$visits = new SplQueue();
		$visits->enqueue($origin);
		$portal_block = (clone $this->block_manager->portal_block_dimensions[WorldManager::DIMENSION_NETHER])->setAxis($axis);
		$portal_block_id = $portal_block->getTypeId();
		$frame_block_id = $this->block_manager->nether_portal_frame_block->getTypeId();
		$transaction = new BlockTransaction($world);
		$changed = 0;
		$frame_blocks = [];
		$length_squared = (new Vector2($this->block_manager->nether_portal_max_height, $this->block_manager->nether_portal_max_width))->lengthSquared();
		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->dequeue();
			if($origin->distanceSquared($coordinates) >= $length_squared){
				return null;
			}

			if($transaction->fetchBlockAt($coordinates->x, $coordinates->y, $coordinates->z)->getTypeId() === $portal_block_id){
				continue;
			}

			$block = $world->getBlockAt($coordinates->x, $coordinates->y, $coordinates->z);
			$block_type_id = $block->getTypeId();
			if($block_type_id === BlockTypeIds::AIR){
				$transaction->addBlockAt($coordinates->x, $coordinates->y, $coordinates->z, $portal_block);
				if($axis === Axis::Z){
					$visits->enqueue($coordinates->getSide(Facing::NORTH));
					$visits->enqueue($coordinates->getSide(Facing::SOUTH));
				}else{
					assert($axis === Axis::X);
					$visits->enqueue($coordinates->getSide(Facing::WEST));
					$visits->enqueue($coordinates->getSide(Facing::EAST));
				}
				$visits->enqueue($coordinates->getSide(Facing::UP));
				$visits->enqueue($coordinates->getSide(Facing::DOWN));
				$changed++;
			}elseif($block_type_id !== $frame_block_id){
				return null;
			}else{
				$frame_blocks[] = $block;
			}
		}
		return $changed > 0 ? $transaction : null;
	}

	private function findEndPortalCenterFromFrame(Block $block) : ?Vector3{
		$facing = $block->getFacing();
		$pos = $block->getPosition();
		$left = $block->getSide(Facing::rotateY($facing, false))->hasSameTypeId($block);
		$right = $block->getSide(Facing::rotateY($facing, true))->hasSameTypeId($block);
		if($left && $right){
			return $pos->getSide($facing, 2);
		}
		if($left){
			return $pos->getSide($facing, 2)->getSide(Facing::rotateY($facing, false));
		}
		if($right){
			return $pos->getSide($facing, 2)->getSide(Facing::rotateY($facing, true));
		}
		$facing_block = $block->getSide($facing);
		if($facing_block->getSide(Facing::rotateY($facing, false))->hasSameTypeId($block)){
			return $pos->getSide($facing, 2)->getSide(Facing::rotateY($facing, true));
		}
		if($facing_block->getSide(Facing::rotateY($facing, true))->hasSameTypeId($block)){
			return $pos->getSide($facing, 2)->getSide(Facing::rotateY($facing, false));
		}
		return null;
	}

	/**
	 * @param BlockTransaction $transaction
	 * @param Vector3 $center
	 * @return list<Block>|null
	 */
	private function collectEndPortalFrameBlocks(BlockTransaction $transaction, Vector3 $center) : ?array{
		$blocks = [];
		foreach(Facing::HORIZONTAL as $side){
			$pos = $center->getSide($side, 2);
			$left = $pos->getSide(Facing::rotateY($side, false));
			$right = $pos->getSide(Facing::rotateY($side, true));
			foreach([
				$transaction->fetchBlockAt($pos->x, $pos->y, $pos->z),
				$transaction->fetchBlockAt($left->x, $left->y, $left->z),
				$transaction->fetchBlockAt($right->x, $right->y, $right->z)
			] as $block){
				if(!($block instanceof EndPortalFrame) || !$block->hasEye()){
					return null;
				}
				$blocks[] = $block;
			}
		}
		return $blocks;
	}

	private function createEndPortal(BlockTransaction $transaction, Vector3 $center) : void{
		$portal_block = $this->block_manager->portal_block_dimensions[WorldManager::DIMENSION_END];
		$portal_block_id = $portal_block->getTypeId();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($transaction->fetchBlockAt($center->x + $i, $center->y, $center->z + $j)->getTypeId() !== $portal_block_id){
					$transaction->addBlockAt($center->x + $i, $center->y, $center->z + $j, $portal_block);
				}
			}
		}
	}

	private function destroyEndPortal(BlockTransaction $transaction, Vector3 $center) : void{
		$portal_block_id = $this->block_manager->portal_block_dimensions[WorldManager::DIMENSION_END]->getTypeId();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($transaction->fetchBlockAt($center->x + $i, $center->y, $center->z + $j)->getTypeId() === $portal_block_id){
					$transaction->addBlockAt($center->x + $i, $center->y, $center->z + $j, VanillaBlocks::AIR());
				}
			}
		}
	}

	/**
	 * @param BlockUpdateEvent $event
	 * @priority NORMAL
	 */
	public function onBlockUpdate(BlockUpdateEvent $event) : void{
		$block = $event->getBlock();
		$block_id = $block->getTypeId();
		if(!isset($this->block_manager->portal_block_dimensions_reverse_mapping[$block_id])){
			return;
		}
		$dimension = $this->block_manager->portal_block_dimensions_reverse_mapping[$block_id];
		if($dimension === WorldManager::DIMENSION_NETHER){
			$pos = $block->getPosition();
			$world = $pos->getWorld();
			if(!$this->meetsNetherPortalSupportConditions(new BlockTransaction($world), $pos)){
				$check_sides = [Facing::UP, Facing::DOWN];
				$axis = $block->getAxis();
				if($axis === Axis::X){
					$check_sides[] = Facing::EAST;
					$check_sides[] = Facing::WEST;
				}else{
					assert($axis === Axis::Z);
					$check_sides[] = Facing::NORTH;
					$check_sides[] = Facing::SOUTH;
				}
				$result = Utils::removeTouchingBlocks($world, $block_id, $pos, $check_sides)?->apply() ?? false;
				if($result){
					$event->cancel();
				}
			}
		}elseif($dimension === WorldManager::DIMENSION_END){
			$pos = $block->getPosition();
			if(!$this->meetsEndPortalSupportConditions(new BlockTransaction($pos->getWorld()), $pos)){
				$result = Utils::removeTouchingBlocks($pos->getWorld(), $block_id, $pos, Facing::HORIZONTAL)?->apply();
				if($result){
					$event->cancel();
				}
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority NORMAL
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}
		$block = $event->getBlock();
		$block_id = $block->getTypeId();
		if($block_id === $this->block_manager->nether_portal_frame_block->getTypeId()){
			$item = $event->getItem();
			if($item->getTypeId() === ItemTypeIds::FLINT_AND_STEEL){
				$affectedBlock = $block->getSide($event->getFace());
				if($affectedBlock->getTypeId() === BlockTypeIds::AIR){
					$player = $event->getPlayer();
					$world = $player->getWorld();
					$pos = $affectedBlock->getPosition();
					$transaction = $this->fillNetherPortal($world, $pos, Axis::X, $frame_blocks) ?? $this->fillNetherPortal($world, $pos, Axis::Z, $frame_blocks);
					if($transaction !== null){
						($ev = new PlayerPortalCreateEvent($player, $block->getPosition(), WorldManager::DIMENSION_NETHER, $frame_blocks, $transaction))->call();
						if(!$ev->isCancelled()){
							$transaction->apply();
							$event->cancel();
						}
					}
				}
			}
		}elseif($block_id === $this->block_manager->end_portal_frame_activated_block->getTypeId() && !$block->hasEye()){ // todo: remove hasEye here somehow
			$item = $event->getItem();
			if($item->getTypeId() === $this->block_manager->end_portal_frame_activation_item->getTypeId()){
				$pos = $block->getPosition();
				$transaction = new BlockTransaction($pos->getWorld());
				$transaction->addBlockAt($pos->x, $pos->y, $pos->z, (clone $block)->setEye(true));
				$center = $this->findEndPortalCenterFromFrame($block);
				if($center !== null){
					$frame_blocks = $this->collectEndPortalFrameBlocks($transaction, $center);
					if($frame_blocks !== null){
						$this->createEndPortal($transaction, $center);
						($ev = new PlayerPortalCreateEvent($event->getPlayer(), $pos, WorldManager::DIMENSION_END, $frame_blocks, $transaction))->call();
						if($ev->isCancelled()){
							return;
						}
					}
				}
				if($transaction->apply()){
					$item->pop();
					$event->getPlayer()->getInventory()->setItemInHand($item);
				}
				$event->cancel();
			}
		}elseif($block_id === $this->block_manager->end_portal_frame_unactivated_block->getTypeId() && $block->hasEye()){ // todo: remove hasEye here somehow
			$item = $event->getItem();
			if($item->getTypeId() !== $this->block_manager->end_portal_frame_activation_item->getTypeId()){
				$pos = $block->getPosition();
				$world = $pos->getWorld();
				$transaction = new BlockTransaction($world);
				$transaction->addBlockAt($pos->x, $pos->y, $pos->z, (clone $block)->setEye(false));
				$center = $this->findEndPortalCenterFromFrame($block);
				if($center !== null && $this->collectEndPortalFrameBlocks($transaction, $center) === null){
					$this->destroyEndPortal($transaction, $center);
					$transaction->apply();
					$world->dropItem($pos->add(0.5, 0.75, 0.5), clone $this->block_manager->end_portal_frame_activation_item);
					$event->cancel();
				}
			}
		}
	}

	/**
	 * @param PlayerMoveEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$from = $event->getFrom();
		$from_f = $from->floor();
		$to = $event->getTo();
		$to_f = $to->floor();
		if($from_f->equals($to_f)){
			return;
		}
		$player = $event->getPlayer();
		$from_block = $from->world->getBlockAt($from_f->x, $from_f->y, $from_f->z);
		$to_block = $to->world->getBlockAt($to_f->x, $to_f->y, $to_f->z);
		if(isset($this->block_manager->portal_block_dimensions_reverse_mapping[$from_block->getTypeId()])){
			$this->player_manager->get($player)->onLeavePortal();
		}
		if(isset($this->block_manager->portal_block_dimensions_reverse_mapping[$to_block->getTypeId()])){
			$dimension = $this->block_manager->portal_block_dimensions_reverse_mapping[$to_block->getTypeId()];
			$this->player_manager->get($player)->onEnterPortal($dimension, $to_block->getPosition());
		}
	}

	/**
	 * @param EntityTeleportEvent $event
	 * @priority MONITOR
	 */
	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		if(!($player instanceof Player)){
			return;
		}
		$from_dimension = $this->world_manager->world_dimensions[$event->getFrom()->getWorld()->getFolderName()] ?? $this->world_manager->default_dimension;
		$to_dimension = $this->world_manager->world_dimensions[$event->getTo()->getWorld()->getFolderName()] ?? $this->world_manager->default_dimension;
		if($from_dimension === $to_dimension){
			return;
		}
		// Player can be null if a plugin teleports the player before PlayerLoginEvent @ MONITOR
		$this->player_manager->getNullable($player)?->onBeginDimensionChange($to_dimension, $event->getTo()->asVector3(), !$player->isAlive());
	}
}