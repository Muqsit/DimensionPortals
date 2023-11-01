<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\world;

use muqsit\dimensionportals\Loader;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldCreationOptions;
use RuntimeException;
use function assert;

final class WorldManager{

	private const TYPE_OVERWORLD = 0;
	private const TYPE_NETHER = 1;
	private const TYPE_END = 2;

	/** @var string[] */
	private static array $main_worlds = [];

	/** @var WorldHolder[] */
	private static array $world_type_holders = [];

	/** @var int[] */
	private static array $world_types = [];

	/** @var WorldHolder[] */
	private static array $worlds = [];

	public static function init(Loader $plugin) : void{
		$config = $plugin->getConfiguration();
		self::$main_worlds = [
			self::TYPE_OVERWORLD => $config->overworld->world,
			self::TYPE_NETHER => $config->nether->world,
			self::TYPE_END => $config->end->world
		];

		self::registerWorldHolder(self::TYPE_OVERWORLD, new WorldHolder(fn(World $world) => new WorldInstance($world, DimensionIds::OVERWORLD)));
		self::registerWorldHolder(self::TYPE_NETHER, new WorldHolder(fn(World $world) => new WorldInstance($world, DimensionIds::NETHER)));
		self::registerWorldHolder(self::TYPE_END, new WorldHolder(fn(World $world) => new WorldInstance($world, DimensionIds::THE_END)));

		$dimension_fix = $plugin->getServer()->getPluginManager()->getPlugin("DimensionFix");
		assert($dimension_fix === null || $dimension_fix instanceof \muqsit\dimensionfix\Loader);

		/**
		 * @param string $world
		 * @param self::TYPE_* $type
		 */
		$register_world_type = static function(string $world, int $type) use($dimension_fix) : void{
			if(isset(self::$world_types[$world]) && self::$world_types[$world] !== $type){
				throw new RuntimeException("Tried overriding sub-world {$world}'s dimension from " . self::$world_types[$world] . " to " . $type);
			}

			self::$world_types[$world] = $type;

			if($type !== self::TYPE_OVERWORLD){
				/** @var self::TYPE_NETHER|self::TYPE_END $type */
				$dimension_fix?->applyToWorld($world, match($type){
					self::TYPE_NETHER => DimensionIds::NETHER,
					self::TYPE_END => DimensionIds::THE_END
				});
			}
		};

		$register_world_type($config->overworld->world, self::TYPE_OVERWORLD);
		$register_world_type($config->nether->world, self::TYPE_NETHER);
		$register_world_type($config->end->world, self::TYPE_END);

		foreach($config->nether->sub_worlds as $sub_world){
			$register_world_type($sub_world, self::TYPE_NETHER);
		}

		foreach($config->end->sub_worlds as $sub_world){
			$register_world_type($sub_world, self::TYPE_END);
		}

		$plugin->getServer()->getPluginManager()->registerEvents(new WorldListener(), $plugin);
	}

	public static function register(string $world_name, WorldHolder $holder) : void{
		if(isset(self::$worlds[$world_name])){
			throw new RuntimeException("World {$world_name} is already registered");
		}

		self::$worlds[$world_name] = $holder;

		$world_manager = Server::getInstance()->getWorldManager();
		if(!$world_manager->loadWorld($world_name) && !$world_manager->generateWorld($world_name, WorldCreationOptions::create())){
			throw new RuntimeException("Failed to load world " . $world_name);
		}

		self::$worlds[$world_name]->create($world_manager->getWorldByName($world_name));
	}

	private static function registerWorldHolder(int $type, WorldHolder $holder) : void{
		self::$world_type_holders[$type] = $holder;
		self::register(self::$main_worlds[$type], $holder);
	}

	public static function autoRegister(World $world) : void{
		self::register($world_name = $world->getFolderName(), clone self::$world_type_holders[self::$world_types[$world_name] ?? self::TYPE_OVERWORLD]);
	}

	public static function destroy(World $world) : void{
		if(isset(self::$worlds[$folder = $world->getFolderName()]) && self::$main_worlds[self::$worlds[$folder]->getWorldInstance()->network_dimension_id] === $folder){
			throw new RuntimeException("Tried to unload permanent world " . $folder . " on runtime.");
		}

		unset(self::$worlds[$folder]);
	}

	public static function get(World $world) : ?WorldInstance{
		return isset(self::$worlds[$folder = $world->getFolderName()]) ? self::$worlds[$folder]->getWorldInstance() : null;
	}

	public static function getOverworld() : WorldInstance{
		return self::getFromType(self::TYPE_OVERWORLD);
	}

	public static function getNether() : WorldInstance{
		return self::getFromType(self::TYPE_NETHER);
	}

	public static function getEnd() : WorldInstance{
		return self::getFromType(self::TYPE_END);
	}

	private static function getFromType(int $type) : WorldInstance{
		return self::$worlds[self::$main_worlds[$type]]->getWorldInstance();
	}
}