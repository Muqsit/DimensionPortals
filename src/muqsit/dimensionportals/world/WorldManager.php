<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\world\end\EndWorldInstance;
use muqsit\dimensionportals\world\nether\NetherWorldInstance;
use muqsit\dimensionportals\world\overworld\OverworldInstance;
use pocketmine\Server;
use pocketmine\world\World;
use RuntimeException;

final class WorldManager{

	private const TYPE_OVERWORLD = 0;
	private const TYPE_NETHER = 1;
	private const TYPE_END = 2;

	/** @var string[] */
	private static array $types = [];

	/** @var WorldHolder[] */
	private static array $worlds = [];

	public static function init(Loader $plugin) : void{
		$config = $plugin->getConfig();
		self::$types = [
			self::TYPE_OVERWORLD => $config->getNested("overworld.world"),
			self::TYPE_NETHER => $config->getNested("nether.world"),
			self::TYPE_END => $config->getNested("end.world")
		];

		self::registerWorldHolder(self::TYPE_OVERWORLD, new WorldHolder(OverworldInstance::class));
		self::registerWorldHolder(self::TYPE_NETHER, new WorldHolder(NetherWorldInstance::class));
		self::registerWorldHolder(self::TYPE_END, new WorldHolder(EndWorldInstance::class));

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
		$world = self::getFromType(self::TYPE_OVERWORLD);
		assert($world instanceof OverworldInstance);
		return $world;
	}

	public static function getNether() : NetherWorldInstance{
		$world = self::getFromType(self::TYPE_NETHER);
		assert($world instanceof NetherWorldInstance);
		return $world;
	}

	public static function getEnd() : EndWorldInstance{
		$world = self::getFromType(self::TYPE_END);
		assert($world instanceof EndWorldInstance);
		return $world;
	}

	private static function getFromType(int $type) : WorldInstance{
		return self::$worlds[self::$types[$type]]->getWorldInstance();
	}
}