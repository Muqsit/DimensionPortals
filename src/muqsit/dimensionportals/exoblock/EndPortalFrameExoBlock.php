<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\EndPortalFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\Facing;
use pocketmine\player\Player;
use pocketmine\world\World;
use ReflectionProperty;

class EndPortalFrameExoBlock implements ExoBlock{

	private const SIDES = [Facing::NORTH, Facing::EAST, Facing::SOUTH, Facing::WEST];

	/** @var ReflectionProperty */
	private $property_eye;

	public function __construct(){
		$this->property_eye = new ReflectionProperty(EndPortalFrame::class, "eye");
		$this->property_eye->setAccessible(true);
	}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		/** @var EndPortalFrame $wrapping */
		$eyed = $this->property_eye->getValue($wrapping);
		if(!$eyed){
			if($item->getId() === ItemIds::ENDER_EYE){
				$item->pop();
				$this->property_eye->setValue($wrapping, true);
				$pos = $wrapping->getPos();
				/** @noinspection NullPointerExceptionInspection */
				$pos->getWorld()->setBlockAt($pos->x, $pos->y, $pos->z, $wrapping, false);
				$this->tryCreatingPortal($wrapping);
				return true;
			}
		}elseif($item->getId() !== ItemIds::ENDER_EYE){
			$this->property_eye->setValue($wrapping, false);
			$pos = $wrapping->getPos();
			/** @var World $world */
			$world = $pos->getWorld();
			$world->setBlockAt($pos->x, $pos->y, $pos->z, $wrapping, false);
			$world->dropItem($pos->add(0.5, 0.75, 0.5), ItemFactory::getInstance()->get(ItemIds::ENDER_EYE));
			$this->tryDestroyingPortal($wrapping);
			return true;
		}
		return false;
	}

	public function update(Block $wrapping) : bool{
		if($this->property_eye->getValue($wrapping)){
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
				if(!($block instanceof EndPortalFrame) || !$this->property_eye->getValue($block)){
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
		$pos = $center->getPos();
		/** @var World $world */
		$world = $pos->getWorld();
		$block_factory = BlockFactory::getInstance();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				$world->setBlockAt($pos->x + $i, $pos->y, $pos->z + $j, $block_factory->get(BlockLegacyIds::END_PORTAL), false);
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
		$pos = $center->getPos();
		/** @var World $world */
		$world = $pos->getWorld();
		for($i = -1; $i <= 1; ++$i){
			for($j = -1; $j <= 1; ++$j){
				if($world->getBlockAt($pos->x + $i, $pos->y, $pos->z + $j)->getId() === BlockLegacyIds::END_PORTAL){
					$world->setBlockAt($pos->x + $i, $pos->y, $pos->z + $j, VanillaBlocks::AIR(), false);
				}
			}
		}
	}
}