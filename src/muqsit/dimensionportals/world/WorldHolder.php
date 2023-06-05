<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use Closure;
use pocketmine\world\World;

final class WorldHolder{

	private WorldInstance $instance;

	/**
	 * @param Closure(World) : WorldInstance $builder
	 */
	public function __construct(
		readonly private Closure $builder
	){}

	public function create(World $world) : void{
		$this->instance = ($this->builder)($world);
	}

	public function getWorldInstance() : WorldInstance{
		return $this->instance;
	}
}