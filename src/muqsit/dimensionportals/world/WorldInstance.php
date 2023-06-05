<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\World;

final class WorldInstance{

	/**
	 * @param World $world
	 * @param DimensionIds::* $network_dimension_id
	 */
	public function __construct(
		readonly public World $world,
		readonly public int $network_dimension_id
	){}
}