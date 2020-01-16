<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use Ds\Queue;use muqsit\netherportal\utils\ArrayUtils;use pocketmine\block\Block;
use pocketmine\block\BlockFactory;use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Facing;
use pocketmine\math\Vector2;use pocketmine\math\Vector3;use pocketmine\player\Player;
use pocketmine\world\World;

class PortalFrameExoBlock implements ExoBlock{

	/** @var int */
	private $lengthSquared;

	public function __construct(int $max_portal_height, int $max_portal_width){
		$this->lengthSquared = (new Vector2($max_portal_height, $max_portal_width))->lengthSquared();
	}

	public function onInteract(Block $wrapping, Player $player, Item $item, int $face) : bool{
		if($item->getId() === ItemIds::FLINT_AND_STEEL){
			$affectedBlock = $wrapping->getSide($face);
			if($affectedBlock->getId() === BlockLegacyIds::AIR){
				$pos = $affectedBlock->getPos();
				/** @var World $world */
				$world = $pos->getWorld();

				$blocks = $this->fill($world, $pos, 10, Facing::WEST);
				if(count($blocks) === 0){
					$blocks = $this->fill($world, $pos, 10, Facing::NORTH);
				}
				if(count($blocks) > 0){
					foreach($blocks as $hash => $block){
						if($block->getId() === BlockLegacyIds::PORTAL){
							World::getBlockXYZ($hash, $x, $y, $z);
							$world->setBlockAt($x, $y, $z, $block, false);
						}
					}
					return true;
				}
			}
		}
		return false;
	}

	public function onUpdate(Block $wrapping) : bool{
		return false;
	}

	public function fill(World $world, Vector3 $origin, int $radius, int $direction) : array{
		$blocks = [];

		$visits = new Queue([$origin]);
		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->pop();
			if($origin->distanceSquared($coordinates) >= $this->lengthSquared){
				return [];
			}

			if(
				$world->getBlockAt($coordinates->x, $coordinates->y, $coordinates->z)->getId() === BlockLegacyIds::AIR &&
				ArrayUtils::firstOrDefault(
					$blocks,
					static function(int $hash, Block $block) use($coordinates) : bool{
						World::getBlockXYZ($hash, $x, $y, $z);
						return $coordinates->x === $x && $coordinates->y === $y && $coordinates->z === $z;
					}
				) === null
			){
				$this->visit($coordinates, $blocks, $direction);
				if($direction === Facing::WEST){
					$visits->push(
						$coordinates->getSide(Facing::NORTH),
						$coordinates->getSide(Facing::SOUTH)
					);
				}elseif($direction === Facing::NORTH){
					$visits->push(
						$coordinates->getSide(Facing::WEST),
						$coordinates->getSide(Facing::EAST)
					);
				}
				$visits->push(
					$coordinates->getSide(Facing::UP),
					$coordinates->getSide(Facing::DOWN)
				);
			}else{
				$block = $world->getBlockAt($coordinates->x, $coordinates->y, $coordinates->z);
				if(!$this->isValid($block, $coordinates, $blocks)){
					return [];
				}
			}
		}

		return $blocks;
	}

	public function visit(Vector3 $coordinates, array &$blocks, int $direction) : void{
		$blocks[World::blockHash($coordinates->x, $coordinates->y, $coordinates->z)] = BlockFactory::get(BlockLegacyIds::PORTAL, $direction - 2);
	}

	private function isValid(Block $block, Vector3 $coordinates, array $portals) : bool{
		return $block->getId() === ExoBlockFactory::$FRAME_BLOCK_ID ||
			ArrayUtils::firstOrDefault(
				$portals,
				static function(int $hash, Block $b) use($coordinates) : bool{
					if($b->getId() === BlockLegacyIds::PORTAL){
						World::getBlockXYZ($hash, $x, $y, $z);
						return $coordinates->x === $x && $coordinates->y === $y && $coordinates->z === $z;
					}
					return false;
				}
			) !== null;
	}
}