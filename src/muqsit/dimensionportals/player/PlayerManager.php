<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\player;

use muqsit\dimensionportals\Loader;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

final class PlayerManager{

	/** @var PlayerInstance[] */
	private static $players = [];

	/** @var int[] */
	private static $ticking = [];

	public static function init(Loader $plugin) : void{
		$plugin->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $plugin);
		$plugin->getServer()->getPluginManager()->registerEvents(new PlayerNetworkListener(), $plugin);
		$plugin->getServer()->getPluginManager()->registerEvents(new PlayerDimensionChangeListener(), $plugin);
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(static function() : void{
			foreach(self::$ticking as $player_id){
				self::$players[$player_id]->tick();
			}
		}), 1);
	}

	public static function create(Player $player) : void{
		self::$players[$player->getId()] = new PlayerInstance($player);
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