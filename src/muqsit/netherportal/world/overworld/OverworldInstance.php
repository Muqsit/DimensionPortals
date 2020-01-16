<?php

declare(strict_types=1);

namespace muqsit\netherportal\world\overworld;

use muqsit\netherportal\world\WorldInstance;
use muqsit\netherportal\world\WorldManager;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class OverworldInstance extends WorldInstance{

	public function getNetworkDimensionId() : int{
		return DimensionIds::OVERWORLD;
	}

	public function getParallelUniverse() : WorldInstance{
		return WorldManager::getNether();
	}

	public function onChunkLoad(int $chunkX, int $chunkZ) : void{
	}

	public function onChunkUnload(int $chunkX, int $chunkZ) : void{
	}
}