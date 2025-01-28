<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event;

use muqsit\dimensionportals\WorldManager;
use pocketmine\entity\Location;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;

final class PlayerPortalTeleportEvent extends PlayerEvent implements Cancellable{
	use CancellableTrait;

	/**
	 * @param Player $player
	 * @param WorldManager::DIMENSION_* $dimension
	 * @param Position $block_position
	 * @param Location $target
	 */
	public function __construct(
		Player $player,
		readonly public int $dimension,
		readonly public Position $block_position,
		public Location $target
	){
		$this->player = $player;
	}
}