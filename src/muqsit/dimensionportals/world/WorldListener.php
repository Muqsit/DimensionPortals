<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use muqsit\dimensionportals\player\PlayerManager;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\player\Player;
use pocketmine\Server;

final class WorldListener implements Listener{

	public function __construct(){
		foreach(Server::getInstance()->getWorldManager()->getWorlds() as $world){
			if(WorldManager::get($world) === null){
				WorldManager::autoRegister($world);
			}
		}
	}

	/**
	 * @param WorldLoadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldLoad(WorldLoadEvent $event) : void{
		WorldManager::autoRegister($event->getWorld());
	}

	/**
	 * @param WorldUnloadEvent $event
	 * @priority MONITOR
	 */
	public function onWorldUnload(WorldUnloadEvent $event) : void{
		WorldManager::destroy($event->getWorld());
	}

	/**
	 * @param ChunkLoadEvent $event
	 * @priority MONITOR
	 *//*
	public function onChunkLoad(ChunkLoadEvent $event) : void{
		$chunk = $event->getChunk();
		$world = WorldManager::get($event->getWorld());
		if($world !== null){
			$world->onChunkLoad($chunk->getX(), $chunk->getZ());
		}
	}*/

	/**
	 * @param ChunkUnloadEvent $event
	 * @priority MONITOR
	 *//*
	public function onChunkUnload(ChunkUnloadEvent $event) : void{
		$chunk = $event->getChunk();
		$world = WorldManager::get($event->getWorld());
		if($world !== null){
			$world->onChunkUnload($chunk->getX(), $chunk->getZ());
		}
	}*/

	/**
	 * @param EntityTeleportEvent $event
	 * @priority MONITOR
	 */
	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			$from_world = WorldManager::get($event->getFrom()->getWorld()) ?? WorldManager::getOverworld();
			$to = $event->getTo();
			$to_world = WorldManager::get($to->getWorld()) ?? WorldManager::getOverworld();
			if($from_world->network_dimension_id !== $to_world->network_dimension_id){
				// Player can be null if a plugin teleports the player before PlayerLoginEvent @ MONITOR
				PlayerManager::getNullable($player)?->onBeginDimensionChange($to_world->network_dimension_id, $to->asVector3(), !$player->isAlive());
			}
		}
	}
}