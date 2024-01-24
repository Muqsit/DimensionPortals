<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event\player;

use muqsit\dimensionportals\event\DimensionPortalsEvent;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use pocketmine\world\Position;

class PlayerCreatePortalEvent extends DimensionPortalsEvent implements Cancellable{
	use CancellableTrait;

	/**
	 * @param Player $player
	 * @param Position $block_pos
	 * @param list<Block> $frame_blocks
	 * @param BlockTransaction $transaction
	 */
	public function __construct(
		readonly public Player $player,
		readonly public Position $block_pos,
		readonly public array $frame_blocks,
		readonly public BlockTransaction $transaction
	){}

	final public function getPlayer() : Player{
		return $this->player;
	}

	final public function getBlockPos() : Position{
		return $this->block_pos;
	}
}