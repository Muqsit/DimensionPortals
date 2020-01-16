<?php

declare(strict_types=1);

namespace muqsit\netherportal\player;

use muqsit\netherportal\world\WorldManager;
use pocketmine\player\Player;

final class PlayerInstance{

	/** @var Player */
	private $player;

	/** @var int */
	private $in_portal_ticks = 0;

	/** @var bool */
	private $changing_dimension = false;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function onEnterPortal() : void{
		PlayerManager::scheduleTicking($this->player);
		$this->in_portal_ticks = 0;
	}

	public function onLeavePortal() : void{
		PlayerManager::stopTicking($this->player);
		$this->in_portal_ticks = 0;
	}

	public function onBeginDimensionChange() : void{
		$this->changing_dimension = true;
	}

	public function onEndDimensionChange() : void{
		$this->changing_dimension = false;
	}

	/**
	 * Returns whether the player is on the dimension
	 * changing screen.
	 *
	 * @return bool
	 */
	public function isChangingDimension() : bool{
		return $this->changing_dimension;
	}

	public function tick() : void{
		if(++$this->in_portal_ticks === PlayerManager::$TICKS_BEFORE_TELEPORTING){
			PlayerManager::stopTicking($this->player);
			$this->teleport();
		}
	}

	private function teleport() : void{
		$this->in_portal_ticks = 0;
		(WorldManager::get($this->player->getWorld()) ?? WorldManager::getOverworld())->getParallelUniverse()->addToDimension($this->player);
	}
}