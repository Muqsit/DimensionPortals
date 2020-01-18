<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world\overworld;

use muqsit\dimensionportals\world\WorldInstance;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class OverworldInstance extends WorldInstance{

	public function getNetworkDimensionId() : int{
		return DimensionIds::OVERWORLD;
	}

	public function onChunkLoad(int $chunkX, int $chunkZ) : void{
	}

	public function onChunkUnload(int $chunkX, int $chunkZ) : void{
	}
}