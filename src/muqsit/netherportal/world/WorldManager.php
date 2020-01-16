<?php

declare(strict_types=1);

namespace muqsit\netherportal\world;

use muqsit\netherportal\Loader;
use muqsit\netherportal\world\nether\NetherWorldInstance;
use muqsit\netherportal\world\overworld\OverworldInstance;
use pocketmine\Server;
use pocketmine\world\World;
use RuntimeException;

final class WorldManager{

	public const TYPE_OVERWORLD = 0;
	public const TYPE_NETHER = 1;

	/** @var string[] */
	private static $types = [];

	/** @var WorldHolder[] */
	private static $worlds = [];

	public static function init(Loader $plugin) : void{
		$config = $plugin->getConfig();
		self::$types = [
			self::TYPE_OVERWORLD => $config->getNested("worlds.overworld"),
			self::TYPE_NETHER => $config->getNested("worlds.nether")
		];

		self::registerWorldHolder(self::TYPE_OVERWORLD, new WorldHolder(OverworldInstance::class));
		self::registerWorldHolder(self::TYPE_NETHER, new WorldHolder(NetherWorldInstance::class));

		$plugin->getServer()->getPluginManager()->registerEvents(new WorldListener(), $plugin);
	}

	private static function registerWorldHolder(int $type, WorldHolder $holder) : void{
		$world_name = self::$types[$type];
		self::$worlds[$world_name] = $holder;

		$world_manager = Server::getInstance()->getWorldManager();
		if(!$world_manager->loadWorld($world_name) && !$world_manager->generateWorld($world_name)){
			throw new RuntimeException("Failed to load world " . $world_name);
		}

		self::$worlds[$world_name]->create($world_manager->getWorldByName($world_name));
	}

	public static function destroy(World $world) : void{
		if(isset(self::$worlds[$folder = $world->getFolderName()])){
			throw new RuntimeException("Tried to unload permanent world " . $folder . " on runtime.");
		}
	}

	public static function get(World $world) : ?WorldInstance{
		return isset(self::$worlds[$folder = $world->getFolderName()]) ? self::$worlds[$folder]->getWorldInstance() : null;
	}

	public static function getOverworld() : OverworldInstance{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return self::getFromType(self::TYPE_OVERWORLD);
	}

	public static function getNether() : NetherWorldInstance{
		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return self::getFromType(self::TYPE_NETHER);
	}

	public static function getFromType(int $type) : WorldInstance{
		return self::$worlds[self::$types[$type]]->getWorldInstance();
	}

	public static function isOfType(World $world, int $type) : bool{
		return self::$types[$type] === $world->getFolderName();
	}
}