<?php

declare(strict_types=1);

namespace muqsit\netherportal\world\nether;

use muqsit\netherportal\world\WorldInstance;
use muqsit\netherportal\world\WorldManager;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class NetherWorldInstance extends WorldInstance{

	public function getNetworkDimensionId() : int{
		return DimensionIds::NETHER;
	}

	public function getParallelUniverse() : WorldInstance{
		return WorldManager::getOverworld();
	}

	public function onChunkLoad(int $chunkX, int $chunkZ) : void{
	}

	public function onChunkUnload(int $chunkX, int $chunkZ) : void{
	}
}