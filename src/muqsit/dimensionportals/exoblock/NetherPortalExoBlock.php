<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use Ds\Queue;

use muqsit\dimensionportals\world\WorldInstance;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\utils\SubChunkExplorer;
use pocketmine\world\World;

class NetherPortalExoBlock extends PortalExoBlock{

	/** @var int */
	private $frame_block_id;

	public function __construct(int $teleportation_duration, Block $frame_block){
		parent::__construct($teleportation_duration);
		$this->frame_block_id = $frame_block->getId();
	}

	public function getTargetWorldInstance() : WorldInstance{
		return WorldManager::getNether();
	}

	public function update(Block $wrapping) : bool{
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

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		return false;
	}

	public function isValid(Block $block) : bool{
		$blockId = $block->getId();
		return $blockId === $this->frame_block_id || $blockId === BlockLegacyIds::PORTAL;
	}

	public function fill(World $world, Vector3 $origin, int $metadata) : void{
		$visits = new Queue([$origin]);

		$iterator = new SubChunkExplorer($world);
		$air = VanillaBlocks::AIR();

		$block_factory = BlockFactory::getInstance();

		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->pop();
			if(
				!$iterator->moveTo($coordinates->x, $coordinates->y, $coordinates->z, false) ||
				$block_factory->fromFullBlock($iterator->currentSubChunk->getFullBlock($coordinates->x & 0x0f, $coordinates->y & 0x0f, $coordinates->z & 0x0f))->getId() !== BlockLegacyIds::PORTAL
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