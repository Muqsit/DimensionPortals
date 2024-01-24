<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\event\player\PlayerCreateNetherPortalEvent;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\NetherPortal;
use pocketmine\item\Item;
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

class NetherPortalFrameExoBlock implements ExoBlock{

	readonly private int $frame_block_id;
	readonly private NetherPortal $portal_block;
	readonly private float $length_squared;

	public function __construct(Block $frame_block, NetherPortal $portal_block, int $max_portal_height, int $max_portal_width){
		$this->frame_block_id = $frame_block->getTypeId();
		$this->portal_block = $portal_block;
		$this->length_squared = (new Vector2($max_portal_height, $max_portal_width))->lengthSquared();
	}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		if($item->getTypeId() === ItemTypeIds::FLINT_AND_STEEL){
			$affectedBlock = $wrapping->getSide($face);
			if($affectedBlock->getTypeId() === BlockTypeIds::AIR){
				$world = $player->getWorld();
				$pos = $affectedBlock->getPosition();
				$transaction = $this->fill($world, $pos, Axis::X, $frame_blocks) ?? $this->fill($world, $pos, Axis::Z, $frame_blocks);
				if($transaction !== null){
					($ev = new PlayerCreateNetherPortalEvent($player, $wrapping->getPosition(), $frame_blocks, $transaction))->call();
					if(!$ev->isCancelled()){
						$transaction->apply();
						return true;
					}
				}
			}
		}
		return false;
	}

	public function update(Block $wrapping) : bool{
		return false;
	}

	public function onPlayerMoveInside(Player $player, Block $block) : void{
	}

	public function onPlayerMoveOutside(Player $player, Block $block) : void{
	}

	/**
	 * @param World $world
	 * @param Vector3 $origin
	 * @param Axis::X|Axis::Z $axis
	 * @param list<Block>|null $frame_blocks
	 * @param-out list<Block> $frame_blocks
	 * @return BlockTransaction|null
	 */
	public function fill(World $world, Vector3 $origin, int $axis, ?array &$frame_blocks) : ?BlockTransaction{
		$visits = new SplQueue();
		$visits->enqueue($origin);
		$portal_block = (clone $this->portal_block)->setAxis($axis);
		$portal_block_id = $portal_block->getTypeId();
		$transaction = new BlockTransaction($world);
		$changed = 0;
		$frame_blocks = [];
		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->dequeue();
			if($origin->distanceSquared($coordinates) >= $this->length_squared){
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
			}elseif($block_type_id !== $this->frame_block_id){
				return null;
			}else{
				$frame_blocks[] = $block;
			}
		}
		return $changed > 0 ? $transaction : null;
	}
}