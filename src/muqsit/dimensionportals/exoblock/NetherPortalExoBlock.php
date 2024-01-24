<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\world\WorldInstance;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\block\Block;
use pocketmine\block\NetherPortal;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;
use SplQueue;
use function assert;

class NetherPortalExoBlock extends PortalExoBlock{

	readonly private int $frame_block_id;
	readonly private int $portal_block_id;

	public function __construct(int $teleportation_duration, Block $frame_block, Block $portal_block){
		parent::__construct($teleportation_duration);
		$this->frame_block_id = $frame_block->getTypeId();
		$this->portal_block_id = $portal_block->getTypeId();
	}

	public function getTargetWorldInstance() : WorldInstance{
		return WorldManager::getNether();
	}

	public function update(Block $wrapping) : bool{
		assert($wrapping instanceof NetherPortal);

		$pos = $wrapping->getPosition();
		$world = $pos->getWorld();

		$shouldKeep = 1;
		if($pos->y < World::Y_MAX - 1){
			$shouldKeep &= $this->isValid($wrapping->getSide(Facing::UP)) ? 1 : 0;
		}
		if($pos->y > World::Y_MIN){
			$shouldKeep &= $this->isValid($wrapping->getSide(Facing::DOWN)) ? 1 : 0;
		}

		$axis = $wrapping->getAxis();
		if($axis === Axis::Z){
			$shouldKeep &= $this->isValid($wrapping->getSide(Facing::SOUTH)) ? 1 : 0;
			$shouldKeep &= $this->isValid($wrapping->getSide(Facing::NORTH)) ? 1 : 0;
		}else{
			assert($axis === Axis::X);
			$shouldKeep &= $this->isValid($wrapping->getSide(Facing::WEST)) ? 1 : 0;
			$shouldKeep &= $this->isValid($wrapping->getSide(Facing::EAST)) ? 1 : 0;
		}

		if($shouldKeep === 0){
			return $this->fill($world, $pos)?->apply() ?? false;
		}
		return false;
	}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		return false;
	}

	public function isValid(Block $block) : bool{
		$blockId = $block->getTypeId();
		return $blockId === $this->frame_block_id || $blockId === $this->portal_block_id;
	}

	public function fill(World $world, Vector3 $origin) : ?BlockTransaction{
		$visits = new SplQueue();
		$visits->enqueue($origin);
		$air = VanillaBlocks::AIR();
		$transaction = new BlockTransaction($world);
		$filled = 0;
		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->dequeue();
			if($transaction->fetchBlockAt($coordinates->x, $coordinates->y, $coordinates->z)->getTypeId() !== $this->portal_block_id){
				continue;
			}
			$transaction->addBlockAt($coordinates->x, $coordinates->y, $coordinates->z, $air);
			$filled++;
			$visits->enqueue($coordinates->getSide(Facing::NORTH));
			$visits->enqueue($coordinates->getSide(Facing::SOUTH));
			$visits->enqueue($coordinates->getSide(Facing::UP));
			$visits->enqueue($coordinates->getSide(Facing::DOWN));
		}
		return $filled > 0 ? $transaction : null;
	}
}