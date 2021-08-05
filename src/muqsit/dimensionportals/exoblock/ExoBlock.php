<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface ExoBlock{

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool;

	public function update(Block $wrapping) : bool;

	public function onPlayerMoveInside(Player $player, Block $block) : void;

	public function onPlayerMoveOutside(Player $player, Block $block) : void;
}