<?php

declare(strict_types=1);

namespace muqsit\dimensionportals;

use muqsit\dimensionportals\exoblock\BlockManager;
use muqsit\dimensionportals\player\PlayerManager;
use muqsit\dimensionportals\vanilla\ExtraVanillaData;
use pocketmine\plugin\PluginBase;
use RuntimeException;

final class Loader extends PluginBase{

	private BlockManager $block_manager;
	private PlayerManager $player_manager;
	private WorldManager $world_manager;

	protected function onLoad() : void{
		try{
			$this->block_manager = new BlockManager($this);
			$this->player_manager = new PlayerManager();
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
		$this->block_manager->init($this);
		$this->world_manager->init($this);
		$this->player_manager->init($this);
	}

	public function getBlockManager() : BlockManager{
		return $this->block_manager;
	}

	public function getPlayerManager() : PlayerManager{
		return $this->player_manager;
	}

	public function getWorldManager() : WorldManager{
		return $this->world_manager;
	}
}