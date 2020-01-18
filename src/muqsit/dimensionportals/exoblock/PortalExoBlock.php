<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\world\WorldInstance;
use pocketmine\player\Player;

abstract class PortalExoBlock implements ExoBlock{

	/** @var int */
	private $teleportation_duration;

	public function __construct(int $teleportation_duration){
		$this->teleportation_duration = $teleportation_duration;
	}

	final public function getTeleportationDuration() : int{
		return $this->teleportation_duration;
	}

	abstract public function getTargetWorldInstance() : WorldInstance;

	public function onPlayerMoveInside(Player $player) : void{
		PlayerManager::get($player)->onEnterPortal($this);
	}

	public function onPlayerMoveOutside(Player $player) : void{
		PlayerManager::get($player)->onLeavePortal();
	}
}