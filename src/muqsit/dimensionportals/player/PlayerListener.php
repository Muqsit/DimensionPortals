<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use Logger;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;

final class PlayerListener implements Listener{

	private Logger $logger;

	public function __construct(Logger $logger){
		$this->logger = $logger;
	}

	/**
	 * @param PlayerLoginEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		PlayerManager::create($event->getPlayer(), $this->logger);
	}

	/**
	 * @param PlayerQuitEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		PlayerManager::destroy($event->getPlayer());
	}
}