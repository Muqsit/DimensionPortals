<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event\player;

use muqsit\dimensionportals\event\DimensionPortalsEvent;
use muqsit\dimensionportals\exoblock\PortalExoBlock;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PlayerEnterPortalsEvent extends DimensionPortalsEvent implements Cancellable{
	use CancellableTrait;

	/** @var Player */
	private $player;

	/** @var PortalExoBlock */
	private $block;

	/** @var int */
	private $teleport_duration;

	public function __construct(Player $player, PortalExoBlock $block, int $teleport_duration){
		$this->player = $player;
		$this->block = $block;
		$this->teleport_duration = $teleport_duration;
	}

	public function getPlayer() : Player{
		return $this->player;
	}

	public function getBlock() : PortalExoBlock{
		return $this->block;
	}

	public function getTeleportDuration() : int{
		return $this->teleport_duration;
	}

	public function setTeleportDuration(int $teleport_duration) : void{
		$this->teleport_duration = $teleport_duration;
	}
}