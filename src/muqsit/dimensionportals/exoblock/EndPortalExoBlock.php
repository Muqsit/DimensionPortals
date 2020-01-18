<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\world\WorldInstance;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\player\Player;

class EndPortalExoBlock extends PortalExoBlock{

	public function getTargetWorldInstance() : WorldInstance{
		return WorldManager::getEnd();
	}

	public function interact(Block $wrapping, Player $player, Item $item, int $face) : bool{
		return false;
	}

	public function update(Block $wrapping) : bool{
		return false;
	}
}