# DimensionPortals
This plugin lets players build nether portals and end portals, and use them to teleport between worlds. This plugin supports 

Although this plugin does not generate nether and end dimension terrain, various plugins that do so exist.
Therefore, you may want to supplement DimensionPortals with the following plugins:
- [BetterGen](https://github.com/Ad5001/BetterGen) - Generates more enhanced-looking biomes (if you are looking to build custom dimensions)
- [MultiWorld](https://github.com/CzechPMDevs/MultiWorld) - Generates overworld, nether, and end terrain (and a few kinds of custom terrain)
- [VanillaGenerator](https://github.com/Muqsit/VanillaGenerator) - Generates overworld and nether terrain

As of Minecraft Bedrock Edition v1.19.0, the following plugin(s) **are required** as a fix:
- [DimensionFix](https://github.com/Muqsit/DimensionFix) - Fixes appearance of "ghost" blocks in nether and end dimensions. Simply install this plugin and DimensionPortals integrates seamlessly with it. No additional configuration needed.

## Features
- **Multiple worlds supported**: Have a `nether` world? Have a `nether2` as well? No worries, configure them both as nether.
- **Extremely fast loading screen**: Do not keep players waiting longer than they need to!

## Developer Documentation

### Check if a player is on dimension change screen
```php
use muqsit\dimensionportals\event\PlayerDimensionScreenChangeEvent;
use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\WorldManager;
use pocketmine\player\Player;
use pocketmine\Server;

// Method 1: query current state
/** @var Loader $plugin */
$plugin = Server::getInstance()->getPluginManager()->getPlugin("DimensionPortals");
$player = Server::getInstance()->getPlayerExact("BoxierChimera37")
$manager = $plugin->getPlayerManager();
$changing_dimension = $manager->get($player)->getChangingDimension();
if($changing_dimension !== null){
	echo $player->getName(), " is changing dimension to ", $changing_dimension, "!", PHP_EOL;
}else{
	echo  $player->getName(), " is not changing dimensions!", PHP_EOL;
}

// Method 2: event-driven solution
public function onDimensionScreenChange(PlayerDimensionScreenChangeEvent $event) : void{
	if($event->state === PlayerDimensionScreenChangeEvent::STATE_BEGIN){
		echo $player->getName(), " is now on dimension change screen (dimension=", $event->dimension, ")", PHP_EOL;
	}elseif($event->state === PlayerDimensionScreenChangeEvent::STATE_END){
		echo $player->getName(), " is no longer on dimension change screen (dimension=", $event->dimension, ")", PHP_EOL;
	}
}
```

### Configure dimensions of worlds programatically
```php
use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\WorldManager;
use pocketmine\Server;

/** @var Loader $plugin */
$plugin = Server::getInstance()->getPluginManager()->getPlugin("DimensionPortals");
$manager = $plugin->getWorldManager();
$manager->world_dimensions["ruins"] = WorldManager::DIMENSION_END; // set dimension of 'ruins' world to end
$manager->world_dimensions["nether2"] = WorldManager::DIMENSION_NETHER;
$manager->default_worlds[WorldManager::DIMENSION_OVERWORLD] = "world2"; // portals tp players back to this world
$manager->default_worlds[WorldManager::DIMENSION_NETHER] = "nether2"; // nether portal tps to this world
$manager->default_dimension = WorldManager::DIMENSION_NETHER; // default dimension of the server is nether
```

### PlayerPortalCreateEvent: Disable creating nether portal 64 blocks within spawn
```php
use muqsit\dimensionportals\event\PlayerPortalCreateEvent;
use muqsit\dimensionportals\WorldManager;
use pocketmine\math\Vector3;

public function onPortalCreate(PlayerPortalCreateEvent $event) : void{
	if($event->dimension !== WorldManager::WORLD_NETHER){ // did not create nether portal
		return;
	}
	$player = $event->getPlayer();
	$spawn = $player->getWorld()->getSpawnLocation();
	foreach($event->frame_blocks as $block){
		// the obsidian block
		// these blocks already exist in the world
		if($block->getPosition()->distance($spawn) <= 64){
			$event->cancel();
			return;
		}
	}
	foreach($event->transaction->getBlocks() as [$x, $y, $z, $block]){
		// the pink portal block
		// these are the blocks that will be created
		$pos = new Vector3($x, $y, $z);
		if($pos->distance($spawn) <= 64){
			$event->cancel();
			return;
		}
	}
}
```

### PlayerPortalEnterEvent: Instant teleportation for ranked players
```php
use muqsit\dimensionportals\event\PlayerPortalEnterEvent;

public function onPortalEnter(PlayerPortalEnterEvent $event) : void{
	if($event->getPlayer()->hasPermission("rank.vip")){
		$event->teleport_duration = 0;
	}
}
```

### PlayerPortalTeleportEvent: Make end portals teleport to boring_end world for unranked players
```php
use muqsit\dimensionportals\event\PlayerPortalTeleportEvent;
use muqsit\dimensionportals\WorldManager;
use pocketmine\entity\Location;

public function onPortalTeleport(PlayerPortalTeleportEvent $event) : void{
	if($event->dimension !== WorldManager::DIMENSION_END){
		return;
	}
	$player = $event->getPlayer();
	if($player->hasPermission("rank.vip")){
		return;
	}
	$pos = $player->getServer()->getWorldManager()->getWorldByName("boring_end")->getSpawnLocation();
	$event->target = Location::fromObject($pos, $pos->world);
}
```

## Resources
- [GlowstoneMC](https://github.com/GlowstoneMC/Glowstone) - End portal creation and destruction logic
- [MiNET](https://github.com/NiclasOlofsson/MiNET) - Nether portal creation and destruction logic