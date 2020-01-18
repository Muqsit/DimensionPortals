<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use InvalidArgumentException;
use muqsit\dimensionportals\Loader;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\Config;

final class ExoBlockFactory{

	/** @var ExoBlock[] */
	private static $blocks = [];

	public static function init(Loader $loader) : void{
		$loader->getServer()->getPluginManager()->registerEvents(new ExoBlockEventHandler(), $loader);
		$config = $loader->getConfig();
		self::initNether($config);
		self::initEnd($config);
	}

	private static function initNether(Config $config) : void{
		$frame_block = VanillaBlocks::fromString((string) $config->getNested("nether.portal.frame-block"));
		if($frame_block->getId() === BlockLegacyIds::AIR){
			throw new InvalidArgumentException("Invalid nether portal frame block " . $config->get("nether.portal.frame-block"));
		}

		self::register(
			new NetherPortalFrameExoBlock(
				$frame_block,
				(int) $config->getNested("nether.portal.max-height"),
				(int) $config->getNested("nether.portal.max-width")
			),
			$frame_block
		);
		self::register(new NetherPortalExoBlock($config->getNested("nether.teleportation-duration"), $frame_block), VanillaBlocks::NETHER_PORTAL());
	}

	private static function initEnd(Config $config) : void{
		self::register(new EndPortalFrameExoBlock(), VanillaBlocks::END_PORTAL_FRAME());
		self::register(new EndPortalExoBlock($config->getNested("end.teleportation-duration")), BlockFactory::get(BlockLegacyIds::END_PORTAL));
	}

	public static function register(ExoBlock $exo_block, Block $block) : void{
		self::$blocks[$block->getRuntimeId()] = $exo_block;
		foreach(BlockFactory::getAllKnownStates() as $state){
			if($state->getId() === $block->getId()){
				self::$blocks[$state->getRuntimeId()] = $exo_block;
			}
		}
	}

	public static function get(Block $block) : ?ExoBlock{
		return self::$blocks[$block->getRuntimeId()] ?? null;
	}
}