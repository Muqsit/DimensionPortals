<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event;

use muqsit\dimensionportals\WorldManager;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\Position;

final class PlayerPortalCreateEvent extends PlayerEvent implements Cancellable{
	use CancellableTrait;

	/**
	 * @param Player $player
	 * @param Position $block_pos
	 * @param WorldManager::DIMENSION_* $dimension
	 * @param list<Block> $frame_blocks
	 * @param BlockTransaction $transaction
	 */
	final public function __construct(
		Player $player,
		readonly public Position $block_pos,
		readonly public int $dimension,
		readonly public array $frame_blocks,
		readonly public BlockTransaction $transaction
	){
		$this->player = $player;
	}
}