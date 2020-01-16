<?php

declare(strict_types=1);

namespace muqsit\netherportal;

use muqsit\netherportal\exoblock\ExoBlockFactory;
use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase{

	protected function onEnable() : void{
		ExoBlockFactory::init($this);
	}

	protected function onDisable() : void{
	}
}