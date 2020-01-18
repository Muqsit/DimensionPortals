<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world\nether;

use muqsit\dimensionportals\world\WorldInstance;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class NetherWorldInstance extends WorldInstance{

	public function getNetworkDimensionId() : int{
		return DimensionIds::NETHER;
	}

	public function onChunkLoad(int $chunkX, int $chunkZ) : void{
	}

	public function onChunkUnload(int $chunkX, int $chunkZ) : void{
	}
}