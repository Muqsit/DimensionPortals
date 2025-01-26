<?php

declare(strict_types=1);

namespace muqsit\dimensionportals;

use muqsit\dimensionfix\Loader as DimensionFixLoader;
use pocketmine\event\EventPriority;
use pocketmine\event\world\WorldLoadEvent;
use pocketmine\event\world\WorldUnloadEvent;
use pocketmine\world\World;
use pocketmine\world\WorldManager as PmWorldManager;
use function gettype;
use function is_array;
use function is_string;

final class WorldManager{

	public const int TYPE_OVERWORLD = 0;
	public const int TYPE_NETHER = 1;
	public const int TYPE_END = 2;

	readonly public PmWorldManager $server_manager;

	/** @var self::TYPE_* */
	public int $default_dimension;

	/** @var array<string, self::TYPE_*> */
	public array $world_dimensions;

	/** @var array<self::TYPE_*, string|null> */
	public array $default_worlds;

	private ?DimensionFixLoader $dimension_fix;

	public function __construct(Loader $plugin){
		$this->server_manager = $plugin->getServer()->getWorldManager();
		$config = $plugin->getConfig();

		$dimension = $config->get("default-dimension");
		$default_dimension = match($dimension){
			"overworld" => self::TYPE_OVERWORLD,
			"nether" => self::TYPE_NETHER,
			"end" => self::TYPE_END,
			default => throw new BadConfigurationException("default-dimension: unexpected dimension type '{$dimension}', expected one of: overworld, nether, end")
		};

		$world_dimensions = [];
		$worlds = $config->get("worlds");
		is_array($worlds) || throw new BadConfigurationException("'worlds' must be an array, got " . gettype($worlds));
		foreach($worlds as $world_folder_name => $dimension_type){
			$this->world_dimensions[(string) $world_folder_name] = match($dimension_type){
				"overworld" => self::TYPE_OVERWORLD,
				"nether" => self::TYPE_NETHER,
				"end" => self::TYPE_END,
				default => throw new BadConfigurationException("worlds: unexpected dimension type '{$dimension_type}', expected one of: overworld, nether, end")
			};
		}

		$default_worlds = [
			self::TYPE_OVERWORLD => null,
			self::TYPE_NETHER => null,
			self::TYPE_END => null
		];
		$worlds = $config->get("default-worlds");
		is_array($worlds) || throw new BadConfigurationException("'default-worlds' must be an array, got " . gettype($worlds));
		foreach($worlds as $dimension_type => $world_folder_name){
			$dimension_id = match($dimension_type){
				"overworld" => self::TYPE_OVERWORLD,
				"nether" => self::TYPE_NETHER,
				"end" => self::TYPE_END,
				default => throw new BadConfigurationException("default-worlds: unexpected dimension type '{$dimension_type}', expected one of: overworld, nether, end")
			};
			is_string($world_folder_name) || throw new BadConfigurationException("default-worlds-{$dimension_type}: world name must be string, got " . gettype($world_folder_name));
			$default_worlds[$dimension_id] = $world_folder_name;
		}

		$this->default_dimension = $default_dimension;
		$this->world_dimensions = $world_dimensions;
		$this->default_worlds = $default_worlds;
	}

	public function init(Loader $plugin) : void{
		$this->dimension_fix = $plugin->getServer()->getPluginManager()->getPlugin("DimensionFix");
		$manager = $plugin->getServer()->getPluginManager();
		$manager->registerEvent(WorldLoadEvent::class, function(WorldLoadEvent $event) : void{
			$this->doWorldLoad($event->getWorld());
		}, EventPriority::MONITOR, $plugin);
		$manager->registerEvent(WorldUnloadEvent::class, function(WorldUnloadEvent $event) : void{
			$this->doWorldUnload($event->getWorld());
		}, EventPriority::MONITOR, $plugin);
		foreach($this->server_manager->getWorlds() as $world){
			$this->doWorldLoad($world);
		}
	}

	public function doWorldLoad(World $world) : void{
		$name = $world->getFolderName();
		if($this->dimension_fix !== null && isset($this->world_dimensions[$name])){
			$this->dimension_fix->applyToWorld($name, Utils::coreDimensionToNetwork($this->world_dimensions[$name]));
		}
	}

	public function doWorldUnload(World $world) : void{
		$name = $world->getFolderName();
		if($this->dimension_fix !== null && isset($this->world_dimensions[$name])){
			$this->dimension_fix->unapplyFromWorld($name);
		}
	}
}