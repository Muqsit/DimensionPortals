<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use pocketmine\world\World;

abstract class WorldInstance{

	/** @var World */
	protected $world;

	final public function __construct(World $world){
		$this->world = $world;
	}

	final public function getWorld() : World{
		return $this->world;
	}

	abstract public function getNetworkDimensionId() : int;

	abstract public function onChunkLoad(int $chunkX, int $chunkZ) : void;

	abstract public function onChunkUnload(int $chunkX, int $chunkZ) : void;
}