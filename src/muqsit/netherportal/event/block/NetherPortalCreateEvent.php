<?php

declare(strict_types=1);

namespace muqsit\netherportal\event\block;

use muqsit\netherportal\event\NetherPortalEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\math\Vector3;

class NetherPortalCreateEvent extends NetherPortalEvent implements Cancellable{
	use CancellableTrait;

	/** @var Vector3[] */
	private $portal_blocks;

	public function __construct(array $portal_blocks){
		$this->portal_blocks = $portal_blocks;
	}

	/**
	 * @return Vector3[]
	 */
	public function getPortalBlocks() : array{
		return $this->portal_blocks;
	}
}