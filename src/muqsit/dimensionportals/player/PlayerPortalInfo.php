<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\exoblock\PortalExoBlock;

final class PlayerPortalInfo{

	/** @var PortalExoBlock */
	private $block;

	/** @var int */
	private $duration = 0;

	/** @var int */
	private $max_duration;

	public function __construct(PortalExoBlock $block, int $max_duration){
		$this->block = $block;
		$this->max_duration = $max_duration;
	}

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