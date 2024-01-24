<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\world\WorldInstance;
use muqsit\dimensionportals\world\WorldManager;
use muqsit\dimensionportals\world\WorldUtils;
use pocketmine\block\Block;
use pocketmine\block\NetherPortal;
use pocketmine\item\Item;
use pocketmine\math\Axis;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;
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

	public function meetsSupportConditions(BlockTransaction $transaction, Vector3 $pos) : bool{
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
			if(!$this->isValid($block)){
				return false;
			}
		}
		return true;
	}

	public function update(Block $wrapping) : bool{
		assert($wrapping instanceof NetherPortal);
		$pos = $wrapping->getPosition();
		$world = $pos->getWorld();
		if(!$this->meetsSupportConditions(new BlockTransaction($world), $pos)){
			$check_sides = [Facing::UP, Facing::DOWN];
			$axis = $wrapping->getAxis();
			if($axis === Axis::X){
				$check_sides[] = Facing::EAST;
				$check_sides[] = Facing::WEST;
			}else{
				assert($axis === Axis::Z);
				$check_sides[] = Facing::NORTH;
				$check_sides[] = Facing::SOUTH;
			}
			return WorldUtils::removeTouchingBlocks($world, $this->portal_block_id, $pos, $check_sides)?->apply() ?? false;
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
}