<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event;

use muqsit\dimensionportals\WorldManager;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

final class PlayerDimensionScreenChangeEvent extends PlayerEvent{

	public const STATE_BEGIN = 0;
	public const STATE_END = 1;

	/**
	 * @param Player $player
	 * @param WorldManager::DIMENSION_* $dimension
	 * @param self::STATE_* $state
	 */
	public function __construct(
		Player $player,
		readonly public int $dimension,
		readonly public int $state
	){
		$this->player = $player;
	}
}