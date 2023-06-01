<?php

declare(strict_types=1);

namespace muqsit\dimensionportals;

use muqsit\dimensionportals\config\BadConfigurationException;
use muqsit\dimensionportals\config\Configuration;
use muqsit\dimensionportals\exoblock\ExoBlockFactory;
use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\vanilla\ExtraVanillaData;
use muqsit\dimensionportals\world\WorldManager;
use pocketmine\plugin\PluginBase;
use RuntimeException;

final class Loader extends PluginBase{

	private Configuration $configuration;

	protected function onLoad() : void{
		try{
			$this->configuration = Configuration::fromData($this->getConfig()->getAll());
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
		WorldManager::init($this);
	}

	public function getConfiguration() : Configuration{
		return $this->configuration;
	}
}