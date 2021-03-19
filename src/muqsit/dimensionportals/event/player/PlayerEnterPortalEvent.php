<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event\player;

use muqsit\dimensionportals\event\DimensionPortalsEvent;
use muqsit\dimensionportals\exoblock\PortalExoBlock;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class PlayerEnterPortalEvent extends DimensionPortalsEvent implements Cancellable{
	use CancellableTrait;

	private Player $player;
	private PortalExoBlock $block;
	private int $teleport_duration;

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