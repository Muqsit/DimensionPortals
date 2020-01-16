<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface ExoBlock{

	/**
	 * @param Block $wrapping
	 * @param Player $player
	 * @param Item $item
	 * @param int $face
	 * @return bool
	 */
	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool;

	/**
	 * @param Block $wrapping
	 * @@return bool
	 */
	public function update(Block $wrapping) : bool;

	/**
	 * @param Player $player
	 */
	public function onPlayerMoveInside(Player $player) : void;

	/**
	 * @param Player $player
	 */
	public function onPlayerMoveOutside(Player $player) : void;
}