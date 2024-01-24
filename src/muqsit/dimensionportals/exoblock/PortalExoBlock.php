<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\world\WorldInstance;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;

abstract class PortalExoBlock implements ExoBlock{

	public function __construct(
		readonly public int $teleportation_duration
	){}

	abstract public function getTargetWorldInstance() : WorldInstance;

	abstract public function meetsSupportConditions(BlockTransaction $transaction, Vector3 $pos) : bool;

	public function onPlayerMoveInside(Player $player, Block $block) : void{
		PlayerManager::get($player)->onEnterPortal($this, $block->getPosition());
	}

	public function onPlayerMoveOutside(Player $player, Block $block) : void{
		PlayerManager::get($player)->onLeavePortal();
	}
}