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

	public function __construct(
		readonly private Block $portal_block,
		readonly private Item $ender_eye_item
	){}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		/** @var EndPortalFrame $wrapping */
		if(!$wrapping->hasEye()){
			if($item->getTypeId() === $this->ender_eye_item->getTypeId()){
				$pos = $wrapping->getPosition();
				$transaction = new BlockTransaction($pos->getWorld());
				$transaction->addBlockAt($pos->x, $pos->y, $pos->z, (clone $wrapping)->setEye(true));
				$center = $this->findPortalCenterFromFrame($wrapping);
				if($center !== null && $this->isCompletedPortal($transaction, $center)){
					$this->createPortal($transaction, $center);
					($ev = new PlayerCreateEndPortalEvent($player, $pos, $transaction))->call();
					if($ev->isCancelled()){
						return true;
					}
				}
				if($transaction->apply()){
					$item->pop();
				}
			}
		}elseif($item->getTypeId() !== $this->ender_eye_item->getTypeId()){
			$pos = $wrapping->getPosition();
			$world = $pos->getWorld();
			$transaction = new BlockTransaction($world);
			$transaction->addBlockAt($pos->x, $pos->y, $pos->z, (clone $wrapping)->setEye(false));
			$center = $this->findPortalCenterFromFrame($wrapping);
			if($center !== null && !$this->isCompletedPortal($transaction, $center)){
				$this->destroyPortal($transaction, $center);
				$transaction->apply();
				$world->dropItem($pos->add(0.5, 0.75, 0.5), $this->ender_eye_item);
			}
			return true;
		}
		return false;
	}

	public function update(Block $wrapping) : bool{
		/** @var EndPortalFrame $wrapping */
		$center = $this->findPortalCenterFromFrame($wrapping);
		if($center !== null){
			$transaction = new BlockTransaction($wrapping->getPosition()->getWorld());
			if($this->isCompletedPortal($transaction, $center)){
				$this->createPortal($transaction, $center);
			}else{
				$this->destroyPortal($transaction, $center);
			}
			$transaction->apply();
		}
		return false;
	}

	public function onPlayerMoveInside(Player $player, Block $block) : void{
	}

	public function onPlayerMoveOutside(Player $player, Block $block) : void{
	}

	public function findPortalCenterFromFrame(EndPortalFrame $block) : ?Vector3{
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

	public function isCompletedPortal(BlockTransaction $transaction, Vector3 $center) : bool{
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
					return false;
				}
			}
		}
		return true;
	}

	public function createPortal(BlockTransaction $transaction, Vector3 $center) : void{
		$type_id = $this->portal_block->getTypeId();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($transaction->fetchBlockAt($center->x + $i, $center->y, $center->z + $j) !== $type_id){
					$transaction->addBlockAt($center->x + $i, $center->y, $center->z + $j, $this->portal_block);
				}
			}
		}
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