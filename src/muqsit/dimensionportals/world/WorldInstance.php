<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\World;

abstract class WorldInstance{

	/**
	 * @param World $world
	 * @param DimensionIds::* $network_dimension_id
	 */
	final public function __construct(
		readonly public World $world,
		readonly public int $network_dimension_id
	){}

	abstract public function onChunkLoad(int $chunkX, int $chunkZ) : void;

	abstract public function onChunkUnload(int $chunkX, int $chunkZ) : void;
}