<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use Logger;
use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\world\WorldManager;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

final class PlayerManager{

	/** @var PlayerInstance[] */
	private static $players = [];

	/** @var int[] */
	private static $ticking = [];

	public static function init(Loader $plugin) : void{
		$plugin->getServer()->getPluginManager()->registerEvents(new PlayerListener($plugin->getLogger()), $plugin);
		$plugin->getServer()->getPluginManager()->registerEvents(new PlayerDimensionChangeListener(), $plugin);

		SimplePacketHandler::createInterceptor($plugin)->interceptOutgoing(static function(StartGamePacket $packet, NetworkSession $target) : bool{
			/** @noinspection NullPointerExceptionInspection */
			$world = WorldManager::get($target->getPlayer()->getWorld());
			if($world !== null){
				$dimensionId = $world->getNetworkDimensionId();
				if($dimensionId !== $packet->spawnSettings->getDimension()){
					$pk = clone $packet;
					$pk->spawnSettings = new SpawnSettings(
						$packet->spawnSettings->getBiomeType(),
						$packet->spawnSettings->getBiomeName(),
						$world->getNetworkDimensionId()
					);
					$target->sendDataPacket($pk);
					return false;
				}
			}
			return true;
		})->interceptIncoming(static function(MovePlayerPacket $packet, NetworkSession $origin) : bool{
			$player = $origin->getPlayer();
			if($player !== null && $player->isConnected() && PlayerManager::get($player)->isChangingDimension()){
				return false;
			}
			return true;
		});

		SimplePacketHandler::createMonitor($plugin)->monitorIncoming(static function(PlayerActionPacket $packet, NetworkSession $origin) : void{
			if($packet->action === PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK){
				$player = $origin->getPlayer();
				if($player !== null && $player->isConnected()){
					PlayerManager::get($player)->onEndDimensionChange();
				}
			}
		});

		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() : void{
			foreach(self::$ticking as $player_id){
				self::$players[$player_id]->tick();
			}
		}), 1);
	}

	public static function create(Player $player, Logger $logger) : void{
		self::$players[$player->getId()] = new PlayerInstance($player, $logger);
	}

	public static function destroy(Player $player) : void{
		self::stopTicking($player);
		unset(self::$players[$player->getId()]);
	}

	public static function get(Player $player) : PlayerInstance{
		return self::getNullable($player);
	}

	public static function getNullable(Player $player) : ?PlayerInstance{
		return self::$players[$player->getId()] ?? null;
	}

	public static function scheduleTicking(Player $player) : void{
		$player_id = $player->getId();
		self::$ticking[$player_id] = $player_id;
	}

	public static function stopTicking(Player $player) : void{
		unset(self::$ticking[$player->getId()]);
	}
}