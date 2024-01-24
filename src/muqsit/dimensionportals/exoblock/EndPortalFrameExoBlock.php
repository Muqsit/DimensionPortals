<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\event\player\PlayerCreateEndPortalEvent;
use pocketmine\block\Block;
use pocketmine\block\EndPortalFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

class EndPortalFrameExoBlock implements ExoBlock{

	private const SIDES = [Facing::NORTH, Facing::EAST, Facing::SOUTH, Facing::WEST];

	public function __construct(
		readonly private Block $portal_block,
		readonly private Item $ender_eye_item
	){}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		/** @var EndPortalFrame $wrapping */
		if(!$wrapping->hasEye()){
			if($item->getTypeId() === $this->ender_eye_item->getTypeId()){
				$pos = $wrapping->getPosition();
				$wrapping->setEye(true);
				$transaction = new BlockTransaction($pos->getWorld());
				$transaction->addBlockAt($pos->x, $pos->y, $pos->z, $wrapping);
				if($this->tryCreatingPortal($transaction, $pos)){
					($ev = new PlayerCreateEndPortalEvent($player, $pos, $transaction))->call();
					if(!$ev->isCancelled()){
						if($transaction->apply()){
							$item->pop();
						}
						return true;
					}
				}
			}
		}elseif($item->getTypeId() !== $this->ender_eye_item->getTypeId()){
			$wrapping->setEye(false);
			$pos = $wrapping->getPosition();
			$world = $pos->getWorld();
			$transaction = new BlockTransaction($world);
			$transaction->addBlockAt($pos->x, $pos->y, $pos->z, $wrapping);
			if($this->tryDestroyingPortal($transaction, $pos) && $transaction->apply()){
				$world->dropItem($pos->add(0.5, 0.75, 0.5), $this->ender_eye_item);
			}
			return true;
		}
		return false;
	}

	public function update(Block $wrapping) : bool{
		/** @var EndPortalFrame $wrapping */
		if($wrapping->hasEye()){
			$pos = $wrapping->getPosition();
			$transaction = new BlockTransaction($pos->getWorld());
			if($this->tryDestroyingPortal($transaction, $pos)){
				$transaction->apply();
			}
		}
		return false;
	}

	public function onPlayerMoveInside(Player $player, Block $block) : void{
	}

	public function onPlayerMoveOutside(Player $player, Block $block) : void{
	}

	public function isCompletedPortal(BlockTransaction $transaction, Vector3 $center) : bool{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$pos = $center->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				$block = $transaction->fetchBlockAt($pos->x, $pos->y, $pos->z);
				if(!($block instanceof EndPortalFrame) || !$block->hasEye()){
					return false;
				}
			}
		}

		return true;
	}

	public function tryCreatingPortal(BlockTransaction $transaction, Vector3 $frame_block_pos) : bool{
		$added = 0;
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$center = $frame_block_pos->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if($this->isCompletedPortal($transaction, $center)){
					$this->createPortal($transaction, $center);
					$added++;
				}
			}
		}
		return $added > 0;
	}

	public function createPortal(BlockTransaction $transaction, Vector3 $center) : void{
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$transaction->addBlockAt($center->x + $i, $center->y, $center->z + $j, $this->portal_block);
			}
		}
	}

	public function tryDestroyingPortal(BlockTransaction $transaction, Vector3 $frame_block_pos) : bool{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$center = $frame_block_pos->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if(!$this->isCompletedPortal($transaction, $center)){
					$this->destroyPortal($transaction, $frame_block_pos);
					return true;
				}
			}
		}
		return false;
	}

	public function destroyPortal(BlockTransaction $transaction, Vector3 $center) : void{
		$type_id = $this->portal_block->getTypeId();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($transaction->fetchBlockAt($center->x + $i, $center->y, $center->z + $j)->getTypeId() === $type_id){
					$transaction->addBlockAt($center->x + $i, $center->y, $center->z + $j, VanillaBlocks::AIR());
				}
			}
		}
	}
}