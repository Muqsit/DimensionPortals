<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;

final class PlayerDimensionChangeListener implements Listener{

	private function cancelIfChangingDimension(Player $player, Cancellable $event) : bool{
		$instance = PlayerManager::getNullable($player);
		if($instance !== null && $instance->isChangingDimension()){
			$event->setCancelled();
			return true;
		}
		return false;
	}

	/**
	 * @param EntityDamageEvent $event
	 * @priority LOW
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Player && $this->cancelIfChangingDimension($entity, $event)){
			return;
		}
		if($event instanceof EntityDamageByEntityEvent){
			$damager = $event->getDamager();
			if($damager instanceof Player){
				$this->cancelIfChangingDimension($damager, $event);
			}
		}
	}

	/**
	 * @param PlayerInteractEvent $event
	 * @priority LOW
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$this->cancelIfChangingDimension($event->getPlayer(), $event);
	}

	/**
	 * @param PlayerItemUseEvent $event
	 * @priority LOW
	 */
	public function onPlayerItemUse(PlayerItemUseEvent $event) : void{
		$this->cancelIfChangingDimension($event->getPlayer(), $event);
	}

	/**
	 * @param PlayerMoveEvent $event
	 * @priority LOW
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$this->cancelIfChangingDimension($event->getPlayer(), $event);
	}

	/**
	 * @param BlockPlaceEvent $event
	 * @priority LOW
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$this->cancelIfChangingDimension($event->getPlayer(), $event);
	}

	/**
	 * @param BlockBreakEvent $event
	 * @priority LOW
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$this->cancelIfChangingDimension($event->getPlayer(), $event);
	}
}