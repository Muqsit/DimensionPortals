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
	public function onInteract(Block $wrapping, Player $player, Item $item, int $face) : bool;

	/**
	 * @param Block $wrapping
	 * @@return bool
	 */
	public function onUpdate(Block $wrapping) : bool;
}