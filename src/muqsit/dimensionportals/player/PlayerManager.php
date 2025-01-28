<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\Utils;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use PrefixedLogger;

final class PlayerManager{

	/** @var array<int, PlayerInstance> */
	private array $players = [];

	/** @var array<int, int> */
	private array $ticking = [];

	/** @var array<int, true> */
	public array $_changing_dimension_sessions = [];

	/** @var array<int, int> */
	public array $_queue_fast_dimension_change_request = [];

	public function __construct(){
	}

	public function init(Loader $plugin) : void{
		$logger = $plugin->getLogger();
		$manager = $plugin->getServer()->getPluginManager();
		$block_manager = $plugin->getBlockManager();
		$world_manager = $plugin->getWorldManager();
		$manager->registerEvent(PlayerLoginEvent::class, function(PlayerLoginEvent $event) use($logger, $block_manager, $world_manager) : void{
			$player = $event->getPlayer();
			$this->players[$player->getId()] = new PlayerInstance($this, $block_manager, $world_manager, $player, new PrefixedLogger($logger, $player->getName()));
		}, EventPriority::MONITOR, $plugin);
		$manager->registerEvent(PlayerQuitEvent::class, function(PlayerQuitEvent $event) : void{
			$this->destroy($event->getPlayer());
		}, EventPriority::MONITOR, $plugin);
		$this->registerNetworkHandlers($plugin);
		$this->registerLockPlayerHandlers($plugin);
	}

	private function registerNetworkHandlers(Loader $plugin) : void{
		$world_manager = $plugin->getWorldManager();
		SimplePacketHandler::createInterceptor($plugin)->interceptOutgoing(function(StartGamePacket $packet, NetworkSession $target) use($world_manager) : bool{
			$player = $target->getPlayer();
			if($player === null){
				return true;
			}
			$core_dimension = $world_manager->world_dimensions[$player->getWorld()->getFolderName()] ?? $world_manager->default_dimension;
			$network_dimension = Utils::coreDimensionToNetwork($core_dimension);
			if($network_dimension === $packet->levelSettings->spawnSettings->getDimension()){
				return true;
			}
			$_packet = clone $packet;
			$_packet->levelSettings->spawnSettings = new SpawnSettings(
				$packet->levelSettings->spawnSettings->getBiomeType(),
				$packet->levelSettings->spawnSettings->getBiomeName(),
				$network_dimension
			);
			$target->sendDataPacket($_packet);
			return false;
		})->interceptIncoming(function(MovePlayerPacket $packet, NetworkSession $origin) : bool{
			return !isset($this->_changing_dimension_sessions[spl_object_id($origin)]);
		})->interceptIncoming(function(PlayerAuthInputPacket $packet, NetworkSession $origin) : bool{
			return !isset($this->_changing_dimension_sessions[spl_object_id($origin)]);
		});
		SimplePacketHandler::createMonitor($plugin)->monitorIncoming(function(PlayerActionPacket $packet, NetworkSession $origin) : void{
			if($packet->action !== PlayerAction::DIMENSION_CHANGE_ACK){
				return;
			}
			$player = $origin->getPlayer();
			if($player === null || !$player->isConnected()){
				return;
			}
			$instance = $this->get($player);
			if($instance->getChangingDimension() !== null){
				$instance->onEndDimensionChange();
			}
		});
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			foreach($this->ticking as $player_id){
				$this->players[$player_id]->tick();
			}
			foreach($this->_queue_fast_dimension_change_request as $id){
				if(isset($this->players[$id])){
					$player = $this->players[$id]->player;
					$location = BlockPosition::fromVector3($player->getLocation());
					$player->getNetworkSession()->sendDataPacket(PlayerActionPacket::create($player->getId(), PlayerAction::DIMENSION_CHANGE_ACK, $location, $location, 0));
				}
			}
			$this->_queue_fast_dimension_change_request = [];
		}), 1);
	}

	private function registerLockPlayerHandlers(Loader $plugin) : void{
		$is_changing_dimension = function(Player $player) : bool{
			$instance = $this->getNullable($player);
			return $instance !== null && $instance->getChangingDimension() !== null;
		};
		$manager = $plugin->getServer()->getPluginManager();
		$manager->registerEvent(EntityDamageEvent::class, function(EntityDamageEvent $event) use($is_changing_dimension) : void{
			$entity = $event->getEntity();
			if($entity instanceof Player && $is_changing_dimension($entity)){
				$event->cancel();
				return;
			}
			if($event instanceof EntityDamageByEntityEvent){
				$damager = $event->getDamager();
				if($damager instanceof Player && $is_changing_dimension($damager)){
					$event->cancel();
				}
			}
		}, EventPriority::LOW, $plugin);
		$manager->registerEvent(PlayerInteractEvent::class, static function(PlayerInteractEvent $event) use($is_changing_dimension) : void{
			if($is_changing_dimension($event->getPlayer())){
				$event->cancel();
			}
		}, EventPriority::LOW, $plugin);
		$manager->registerEvent(PlayerItemUseEvent::class, static function(PlayerItemUseEvent $event) use($is_changing_dimension) : void{
			if($is_changing_dimension($event->getPlayer())){
				$event->cancel();
			}
		}, EventPriority::LOW, $plugin);
		$manager->registerEvent(PlayerMoveEvent::class, static function(PlayerMoveEvent $event) use($is_changing_dimension) : void{
			if($is_changing_dimension($event->getPlayer())){
				$event->cancel();
			}
		}, EventPriority::LOW, $plugin);
		$manager->registerEvent(BlockPlaceEvent::class, static function(BlockPlaceEvent $event) use($is_changing_dimension) : void{
			if($is_changing_dimension($event->getPlayer())){
				$event->cancel();
			}
		}, EventPriority::LOW, $plugin);
		$manager->registerEvent(BlockBreakEvent::class, static function(BlockBreakEvent $event) use($is_changing_dimension) : void{
			if($is_changing_dimension($event->getPlayer())){
				$event->cancel();
			}
		}, EventPriority::LOW, $plugin);
	}

	public function destroy(Player $player) : void{
		$this->stopTicking($player);
		unset($this->players[$player->getId()]);
	}

	public function get(Player $player) : PlayerInstance{
		return $this->players[$player->getId()];
	}

	public function getNullable(Player $player) : ?PlayerInstance{
		return $this->players[$player->getId()] ?? null;
	}

	public function scheduleTicking(Player $player) : void{
		$player_id = $player->getId();
		$this->ticking[$player_id] = $player_id;
	}

	public function stopTicking(Player $player) : void{
		unset($this->ticking[$player->getId()], $this->_changing_dimension_sessions[spl_object_id($player->getNetworkSession())]);
	}
}