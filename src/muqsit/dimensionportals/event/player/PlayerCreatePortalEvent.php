<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\event\player;

use muqsit\dimensionportals\event\DimensionPortalsEvent;
use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;
use pocketmine\world\Position;

class PlayerCreatePortalEvent extends DimensionPortalsEvent implements Cancellable{
	use CancellableTrait;

	private Player $player;

	private Position $block_pos;

	/** @var Block[] */
	private array $affected_blocks;

	/**
	 * @param Player $player
	 * @param Position $block_pos
	 */
	public function __construct(Player $player, Position $block_pos){
		$this->player = $player;
		$this->block_pos = $block_pos;
	}

	final public function getPlayer() : Player{
		return $this->player;
	}

	final public function getBlockPos() : Position{
		return $this->block_pos;
	}
}