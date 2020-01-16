<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;

final class ExoBlockEventHandler implements Listener{

	/**
	 * @param BlockUpdateEvent $event
	 * @priority NORMAL
	 */
	public function onBlockUpdate(BlockUpdateEvent $event) : void{
		$block = $event->getBlock();
		$exo_block = ExoBlockFactory::get($block);
		if($exo_block !== null && $exo_block->update($block)){
			$event->setCancelled();
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority NORMAL
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$block = $event->getBlock();
		$exo_block = ExoBlockFactory::get($block);
		if($exo_block !== null && $exo_block->interact($block, $event->getPlayer(), $event->getItem(), $event->getFace())){
			$event->setCancelled();
		}
	}

	/**
	 * @param PlayerMoveEvent $event
	 * @priority MONITOR
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$from = $event->getFrom();
		$from_f = $from->floor();

		$to = $event->getTo();
		$to_f = $to->floor();

		if(!$from_f->equals($to_f)){
			$player = $event->getPlayer();
			$from_block = ExoBlockFactory::get($from->world->getBlockAt($from_f->x, $from_f->y, $from_f->z));
			if($from_block !== null){
				$from_block->onPlayerMoveOutside($player);
			}
			$to_block = ExoBlockFactory::get($to->world->getBlockAt($to_f->x, $to_f->y, $to_f->z));
			if($to_block !== null){
				$to_block->onPlayerMoveInside($player);
			}
		}
	}
}