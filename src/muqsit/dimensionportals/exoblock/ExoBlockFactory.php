<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use InvalidArgumentException;
use muqsit\dimensionportals\config\EndConfiguration;
use muqsit\dimensionportals\config\NetherConfiguration;
use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\vanilla\ExtraVanillaBlocks;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;

final class ExoBlockFactory{

	/** @var ExoBlock[] */
	private static array $blocks = [];

	public static function init(Loader $loader) : void{
		$loader->getServer()->getPluginManager()->registerEvents(new ExoBlockEventHandler(), $loader);
		self::initNether($loader->getConfiguration()->nether);
		self::initEnd($loader->getConfiguration()->end);
	}

	private static function initNether(NetherConfiguration $config) : void{
		$frame_block = StringToItemParser::getInstance()->parse($config->portal->frame_block)->getBlock();
		if($frame_block->getTypeId() === BlockTypeIds::AIR){
			throw new InvalidArgumentException("Invalid nether portal frame block " . $config->portal->frame_block);
		}

		self::register(
			new NetherPortalFrameExoBlock(
				$frame_block,
				$config->portal->max_height,
				$config->portal->max_width
			),
			$frame_block
		);
		self::register(new NetherPortalExoBlock($config->teleportation_duration, $frame_block), VanillaBlocks::NETHER_PORTAL());
	}

	private static function initEnd(EndConfiguration $config) : void{
		self::register(new EndPortalFrameExoBlock(), VanillaBlocks::END_PORTAL_FRAME());
		self::register(new EndPortalExoBlock($config->teleportation_duration), ExtraVanillaBlocks::END_PORTAL());
	}

	public static function register(ExoBlock $exo_block, Block $block) : void{
		self::$blocks[$block->getStateId()] = $exo_block;
		foreach(RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state){
			if($state->getTypeId() === $block->getTypeId()){
				self::$blocks[$state->getStateId()] = $exo_block;
			}
		}
	}

	public static function get(Block $block) : ?ExoBlock{
		return self::$blocks[$block->getStateId()] ?? null;
	}
}