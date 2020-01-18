<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world\end;

use muqsit\dimensionportals\world\WorldInstance;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class EndWorldInstance extends WorldInstance{

	public function getNetworkDimensionId() : int{
		return DimensionIds::THE_END;
	}

	public function onChunkLoad(int $chunkX, int $chunkZ) : void{
	}

	public function onChunkUnload(int $chunkX, int $chunkZ) : void{
	}
}