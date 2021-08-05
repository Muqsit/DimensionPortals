<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use muqsit\dimensionportals\player\PlayerManager;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\player\Player;

final class WorldListener implements Listener{

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
			if($from_world->getNetworkDimensionId() !== $to_world->getNetworkDimensionId()){
				PlayerManager::get($player)->onBeginDimensionChange($to_world->getNetworkDimensionId(), $to->asVector3(), !$player->isAlive());
			}
		}
	}
}