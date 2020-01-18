<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\world\WorldManager;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\Server;

final class PlayerNetworkListener implements Listener{

	/**
	 * @param DataPacketSendEvent $event
	 * @priority NORMAL
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$packets = $event->getPackets();
		foreach($packets as $index => $packet){
			if($packet instanceof StartGamePacket){
				$targets = $event->getTargets();
				foreach($targets as $target){
					/** @noinspection NullPointerExceptionInspection */
					$world = WorldManager::get($target->getPlayer()->getWorld());
					if($world !== null){
						$dimensionId = $world->getNetworkDimensionId();
						if($dimensionId !== $packet->dimension){
							$event->setCancelled();
							foreach($targets as $target2){
								/** @noinspection NullPointerExceptionInspection */
								$world = WorldManager::get($target2->getPlayer()->getWorld());
								if($world !== null){
									$pk = clone $packet;
									$pk->dimension = $world->getNetworkDimensionId();
									$target2->sendDataPacket($pk);
								}else{
									$target2->sendDataPacket(clone $packet);
								}
							}

							// don't worry, half the shit over here probably will never execute

							unset($packets[$index]);
							if(count($packets) > 0){
								$target_players = [];
								foreach($targets as $target3){
									$target_players[] = $target3->getPlayer();
								}
								Server::getInstance()->broadcastPackets($target_players, $packets);
							}
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 * @priority MONITOR
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		if($packet instanceof PlayerActionPacket && $packet->action === PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK){
			$player = $event->getOrigin()->getPlayer();
			if($player !== null && $player->isOnline()){
				PlayerManager::get($player)->onEndDimensionChange();
			}
		}
	}
}