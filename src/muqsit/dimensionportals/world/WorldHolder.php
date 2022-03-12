<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use pocketmine\utils\Utils;
use pocketmine\world\World;

final class WorldHolder{

	private WorldInstance $instance;

	/**
	 * @param string $class
	 *
	 * @phpstan-template TWorldInstance of WorldInstance
	 * @phpstan-param class-string<TWorldInstance> $class
	 */
	public function __construct(
		private string $class
	){
		Utils::testValidInstance($this->class, WorldInstance::class);
	}

	public function create(World $world) : void{
		$this->instance = new $this->class($world);
	}

	public function getWorldInstance() : WorldInstance{
		return $this->instance;
	}
}