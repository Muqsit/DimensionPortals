<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use pocketmine\utils\Utils;
use pocketmine\world\World;

final class WorldHolder{

	private string $class;
	private WorldInstance $instance;

	public function __construct(string $class){
		Utils::testValidInstance($class, WorldInstance::class);
		$this->class = $class;
	}

	public function create(World $world) : void{
		$this->instance = new $this->class($world);
	}

	public function getWorldInstance() : WorldInstance{
		return $this->instance;
	}
}