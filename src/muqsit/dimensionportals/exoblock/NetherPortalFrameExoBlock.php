<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\event\player\PlayerCreateNetherPortalEvent;
use muqsit\dimensionportals\utils\ArrayUtils;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Facing;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;
use SplQueue;

class NetherPortalFrameExoBlock implements ExoBlock{

	readonly private int $frame_block_id;
	readonly private float $length_squared;

	public function __construct(Block $frame_block, int $max_portal_height, int $max_portal_width){
		$this->frame_block_id = $frame_block->getTypeId();
		$this->length_squared = (new Vector2($max_portal_height, $max_portal_width))->lengthSquared();
	}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		if($item->getTypeId() === ItemTypeIds::FLINT_AND_STEEL){
			$affectedBlock = $wrapping->getSide($face);
			if($affectedBlock->getTypeId() === BlockTypeIds::AIR){
				$world = $player->getWorld();
				$pos = $affectedBlock->getPosition()->asVector3();
				$blocks = $this->fill($world, $pos, 10, Facing::WEST);
				if(count($blocks) === 0){
					$blocks = $this->fill($world, $pos, 10, Facing::NORTH);
				}
				if(count($blocks) > 0){
					($ev = new PlayerCreateNetherPortalEvent($player, $wrapping->getPosition()))->call();
					if(!$ev->isCancelled()){
						foreach($blocks as $hash => $block){
							if($block->getTypeId() === BlockTypeIds::NETHER_PORTAL){
								World::getBlockXYZ($hash, $x, $y, $z);
								$world->setBlockAt($x, $y, $z, $block, false);
							}
						}
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
	 * @param int $radius
	 * @param int $direction
	 * @return array<int, Block>
	 */
	public function fill(World $world, Vector3 $origin, int $radius, int $direction) : array{
		$blocks = [];

		$visits = new SplQueue();
		$visits->enqueue($origin);
		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->dequeue();
			if($origin->distanceSquared($coordinates) >= $this->length_squared){
				return [];
			}

			$coordinates_hash = World::blockHash($coordinates->x, $coordinates->y, $coordinates->z);
			$block = $world->getBlockAt($coordinates->x, $coordinates->y, $coordinates->z);

			if(
				$block->getTypeId() === BlockTypeIds::AIR &&
				ArrayUtils::firstOrDefault(
					$blocks,
					static function(int $hash, Block $block) use($coordinates_hash) : bool{ return $hash === $coordinates_hash; }
				) === null
			){
				$this->visit($coordinates, $blocks, $direction);
				if($direction === Facing::WEST){
					$visits->enqueue($coordinates->getSide(Facing::NORTH));
					$visits->enqueue($coordinates->getSide(Facing::SOUTH));
				}elseif($direction === Facing::NORTH){
					$visits->enqueue($coordinates->getSide(Facing::WEST));
					$visits->enqueue($coordinates->getSide(Facing::EAST));
				}
				$visits->enqueue($coordinates->getSide(Facing::UP));
				$visits->enqueue($coordinates->getSide(Facing::DOWN));
			}elseif(!$this->isValid($block, $coordinates_hash, $blocks)){
				return [];
			}
		}

		return $blocks;
	}

	/**
	 * @param Vector3 $coordinates
	 * @param array<int, Block> $blocks
	 * @param int $direction
	 */
	public function visit(Vector3 $coordinates, array &$blocks, int $direction) : void{
		$axis = Facing::axis(Facing::rotateY($direction, true));
		$blocks[World::blockHash($coordinates->x, $coordinates->y, $coordinates->z)] = VanillaBlocks::NETHER_PORTAL()->setAxis($axis);
	}

	/**
	 * @param Block $block
	 * @param int $coordinates_hash
	 * @param array<int, Block> $portals
	 * @return bool
	 */
	private function isValid(Block $block, int $coordinates_hash, array $portals) : bool{
		return $block->getTypeId() === $this->frame_block_id ||
			ArrayUtils::firstOrDefault(
				$portals,
				static function(int $hash, Block $b) use($coordinates_hash) : bool{ return $hash === $coordinates_hash && $b->getTypeId() === BlockTypeIds::NETHER_PORTAL; }
			) !== null;
	}
}