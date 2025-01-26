<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\Utils;
use muqsit\dimensionportals\WorldManager;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class EndPortalExoBlock extends PortalExoBlock{

	readonly private int $frame_block_id;
	readonly private int $portal_block_id;

	public function __construct(int $teleportation_duration, Block $frame_block, Block $portal_block){
		parent::__construct($teleportation_duration);
		$this->frame_block_id = $frame_block->getTypeId();
		$this->portal_block_id = $portal_block->getTypeId();
	}

	public function getTargetWorldInstance() : WorldInstance{
		return WorldManager::getEnd();
	}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		return false;
	}

	public function meetsSupportConditions(BlockTransaction $transaction, Vector3 $pos) : bool{
		foreach(Facing::HORIZONTAL as $side){
			$side_pos = $pos->getSide($side);
			$type_id = $transaction->fetchBlockAt($side_pos->x, $side_pos->y, $side_pos->z)->getTypeId();
			if($type_id !== $this->frame_block_id && $type_id !== $this->portal_block_id){
				return false;
			}
		}
		return true;
	}

	public function update(Block $wrapping) : bool{
		$pos = $wrapping->getPosition();
		if(!$this->meetsSupportConditions(new BlockTransaction($pos->getWorld()), $pos)){
			Utils::removeTouchingBlocks($pos->getWorld(), $this->portal_block_id, $pos, Facing::HORIZONTAL)?->apply();
		}
		return false;
	}
}