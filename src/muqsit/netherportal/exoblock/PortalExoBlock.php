<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use Ds\Queue;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\utils\SubChunkIteratorManager;
use pocketmine\world\World;

class PortalExoBlock implements ExoBlock{

	public function onUpdate(Block $wrapping) : bool{
		$pos = $wrapping->getPos();

		/** @var World $world */
		$world = $pos->getWorld();

		$shouldKeep = 1;
		if($pos->y < World::Y_MAX - 1){
			$shouldKeep &= $this->isValid($world->getBlockAt($pos->x, $pos->y + 1, $pos->z));
		}
		if($pos->y > 0){
			$shouldKeep &= $this->isValid($world->getBlockAt($pos->x, $pos->y - 1, $pos->z));
		}

		$metadata = $wrapping->getMeta();
		if($metadata < 2){
			$shouldKeep &= $this->isValid($world->getBlockAt($pos->x - 1, $pos->y, $pos->z));
			$shouldKeep &= $this->isValid($world->getBlockAt($pos->x + 1, $pos->y, $pos->z));
		}else{
			$shouldKeep &= $this->isValid($world->getBlockAt($pos->x, $pos->y, $pos->z - 1));
			$shouldKeep &= $this->isValid($world->getBlockAt($pos->x, $pos->y, $pos->z + 1));
		}

		if($shouldKeep === 0){
			$this->fill($world, $pos, $metadata);
			return true;
		}

		return false;
	}

	public function onInteract(Block $wrapping, Player $player, Item $item, int $face) : bool{
		return false;
	}

	public function isValid(Block $block) : bool{
		$blockId = $block->getId();
		return $blockId === BlockLegacyIds::OBSIDIAN || $blockId === BlockLegacyIds::PORTAL;
	}

	public function fill(World $world, Vector3 $origin, int $metadata) : void{
		$visits = new Queue([$origin]);

		$iterator = new SubChunkIteratorManager($world);
		$air = VanillaBlocks::AIR();

		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->pop();
			if(
				!$iterator->moveTo($coordinates->x, $coordinates->y, $coordinates->z, false) ||
				BlockFactory::fromFullBlock($iterator->currentSubChunk->getFullBlock($coordinates->x & 0x0f, $coordinates->y & 0x0f, $coordinates->z & 0x0f))->getId() !== BlockLegacyIds::PORTAL
			){
				continue;
			}

			$world->setBlockAt($coordinates->x, $coordinates->y, $coordinates->z, $air);

			if($metadata === 0){
				$visits->push(
					$coordinates->getSide(Facing::EAST),
					$coordinates->getSide(Facing::WEST)
				);
			}else{
				$visits->push(
					$coordinates->getSide(Facing::NORTH),
					$coordinates->getSide(Facing::SOUTH)
				);
			}

			$visits->push(
				$coordinates->getSide(Facing::UP),
				$coordinates->getSide(Facing::DOWN)
			);
		}
	}
}