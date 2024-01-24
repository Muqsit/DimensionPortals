<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\event\player\PlayerCreateEndPortalEvent;
use pocketmine\block\Block;
use pocketmine\block\EndPortalFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\player\Player;

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
				($ev = new PlayerCreateEndPortalEvent($player, $wrapping->getPosition()))->call();
				if(!$ev->isCancelled()){
					$item->pop();
					$wrapping->setEye(true);
					$pos = $wrapping->getPosition();
					$pos->getWorld()->setBlockAt($pos->x, $pos->y, $pos->z, $wrapping, false);
					$this->tryCreatingPortal($wrapping);
					return true;
				}
			}
		}elseif($item->getTypeId() !== $this->ender_eye_item->getTypeId()){
			$wrapping->setEye(false);
			$pos = $wrapping->getPosition();
			$world = $pos->getWorld();
			$world->setBlockAt($pos->x, $pos->y, $pos->z, $wrapping, false);
			$world->dropItem($pos->add(0.5, 0.75, 0.5), $this->ender_eye_item);
			$this->tryDestroyingPortal($wrapping);
			return true;
		}
		return false;
	}

	public function update(Block $wrapping) : bool{
		/** @var EndPortalFrame $wrapping */
		if($wrapping->hasEye()){
			$this->tryDestroyingPortal($wrapping);
		}
		return false;
	}

	public function onPlayerMoveInside(Player $player, Block $block) : void{
	}

	public function onPlayerMoveOutside(Player $player, Block $block) : void{
	}

	public function isCompletedPortal(Block $center) : bool{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$block = $center->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if(!($block instanceof EndPortalFrame) || !$block->hasEye()){
					return false;
				}
			}
		}

		return true;
	}

	public function tryCreatingPortal(Block $wrapping) : void{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$center = $wrapping->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if($this->isCompletedPortal($center)){
					$this->createPortal($center);
				}
			}
		}
	}

	public function createPortal(Block $center) : void{
		$pos = $center->getPosition();
		$world = $pos->getWorld();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$world->setBlockAt($pos->x + $i, $pos->y, $pos->z + $j, $this->portal_block, false);
			}
		}
	}

	public function tryDestroyingPortal(Block $block) : void{
		for($i = 0; $i < 4; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$center = $block->getSide(self::SIDES[$i], 2)->getSide(self::SIDES[($i + 1) % 4], $j);
				if(!$this->isCompletedPortal($center)){
					$this->destroyPortal($center);
				}
			}
		}
	}

	public function destroyPortal(Block $center) : void{
		$pos = $center->getPosition();
		$world = $pos->getWorld();
		$type_id = $this->portal_block->getTypeId();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($world->getBlockAt($pos->x + $i, $pos->y, $pos->z + $j)->getTypeId() === $type_id){
					$world->setBlockAt($pos->x + $i, $pos->y, $pos->z + $j, VanillaBlocks::AIR(), false);
				}
			}
		}
	}
}