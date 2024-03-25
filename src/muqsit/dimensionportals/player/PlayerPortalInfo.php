<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\exoblock\PortalExoBlock;
use pocketmine\world\Position;

final class PlayerPortalInfo{

	private int $duration = 0;

	public function __construct(
		readonly public PortalExoBlock $block,
		readonly public Position $block_position,
		readonly public int $max_duration
	){}

	public function tick() : bool{
		if($this->duration === $this->max_duration){
			$this->duration = 0;
			return true;
		}

		++$this->duration;
		return false;
	}
}