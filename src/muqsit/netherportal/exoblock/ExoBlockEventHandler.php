<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

final class ExoBlockEventHandler implements Listener{

	/**
	 * @param BlockUpdateEvent $event
	 * @priority NORMAL
	 */
	public function onBlockUpdate(BlockUpdateEvent $event) : void{
		$block = $event->getBlock();
		$exo_block = ExoBlockFactory::get($block);
		if($exo_block !== null && $exo_block->onUpdate($block)){
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
		if($exo_block !== null && $exo_block->onInteract($block, $event->getPlayer(), $event->getItem(), $event->getFace())){
			$event->setCancelled();
		}
	}
}