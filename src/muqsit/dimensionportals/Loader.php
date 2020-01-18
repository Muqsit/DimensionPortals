<?php

declare(strict_types=1);

namespace muqsit\dimensionportals;

use muqsit\dimensionportals\exoblock\ExoBlockFactory;
use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase{

	protected function onEnable() : void{
		ExoBlockFactory::init($this);
		PlayerManager::init($this);
		WorldManager::init($this);
	}
}