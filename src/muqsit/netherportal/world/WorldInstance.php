<?php

declare(strict_types=1);

namespace muqsit\netherportal\world;

use pocketmine\player\Player;
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

	abstract public function getParallelUniverse() : WorldInstance; // TODO: give this a better name

	abstract public function onChunkLoad(int $chunkX, int $chunkZ) : void;

	abstract public function onChunkUnload(int $chunkX, int $chunkZ) : void;

	final public function addToDimension(Player $player) : void{
		$player->teleport($this->getWorld()->getSafeSpawn());
	}
}