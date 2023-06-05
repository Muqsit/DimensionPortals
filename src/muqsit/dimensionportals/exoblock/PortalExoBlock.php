<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\world\WorldInstance;
use pocketmine\block\Block;
use pocketmine\player\Player;

abstract class PortalExoBlock implements ExoBlock{

	public function __construct(
		readonly public int $teleportation_duration
	){}

	abstract public function getTargetWorldInstance() : WorldInstance;

	public function onPlayerMoveInside(Player $player, Block $block) : void{
		PlayerManager::get($player)->onEnterPortal($this);
	}

	public function onPlayerMoveOutside(Player $player, Block $block) : void{
		PlayerManager::get($player)->onLeavePortal();
	}
}