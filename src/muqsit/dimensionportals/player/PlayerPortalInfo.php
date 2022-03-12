<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\exoblock\PortalExoBlock;

final class PlayerPortalInfo{

	private int $duration = 0;

	public function __construct(
		private PortalExoBlock $block,
		private int $max_duration
	){}

	public function getBlock() : PortalExoBlock{
		return $this->block;
	}

	public function tick() : bool{
		if($this->duration === $this->max_duration){
			$this->duration = 0;
			return true;
		}

		++$this->duration;
		return false;
	}
}