<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\WorldManager;
use pocketmine\world\Position;

final class PlayerPortalInfo{

	private int $duration = 0;

	/**
	 * @param WorldManager::DIMENSION_* $dimension
	 * @param Position $block_position
	 * @param int $max_duration
	 */
	public function __construct(
		readonly public int $dimension,
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