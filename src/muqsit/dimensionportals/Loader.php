<?php

declare(strict_types=1);

namespace muqsit\dimensionportals;

use muqsit\dimensionportals\exoblock\ExoBlockFactory;
use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\vanilla\ExtraVanillaData;
use pocketmine\plugin\PluginBase;
use RuntimeException;

final class Loader extends PluginBase{

	private WorldManager $world_manager;

	protected function onLoad() : void{
		try{
			$this->world_manager = new WorldManager($this);
		}catch(BadConfigurationException $e){
			$this->getLogger()->warning("The plugin failed to load due to bad configuration.");
			$this->getLogger()->warning("Reason: {$e->getMessage()}");
			$this->getLogger()->warning("Delete the configuration file ({$this->getConfig()->getPath()}) to regenerate a fresh configuration.");
			throw new RuntimeException("Failed to load due to bad configuration");
		}
		ExtraVanillaData::registerOnAllThreads($this->getServer()->getAsyncPool());
	}

	protected function onEnable() : void{
		ExoBlockFactory::init($this);
		PlayerManager::init($this);
	}

	public function getWorldManager() : WorldManager{
		return $this->world_manager;
	}

	public function getConfiguration() : {
	}
}