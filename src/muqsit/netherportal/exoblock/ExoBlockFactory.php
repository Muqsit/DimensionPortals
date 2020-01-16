<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use InvalidArgumentException;
use muqsit\netherportal\Loader;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;

final class ExoBlockFactory{

	/** @var int */
	public static $FRAME_BLOCK_ID;

	/** @var ExoBlock[] */
	private static $blocks = [];

	public static function init(Loader $loader) : void{
		$loader->getServer()->getPluginManager()->registerEvents(new ExoBlockEventHandler(), $loader);

		$config = $loader->getConfig();
		$frame_block = VanillaBlocks::fromString((string) $config->get("portal-frame-block"));
		if($frame_block->getId() === BlockLegacyIds::AIR){
			throw new InvalidArgumentException("Invalid nether portal frame block " . $config->get("portal-frame-block"));
		}

		self::$FRAME_BLOCK_ID = $frame_block->getId();

		self::register(new PortalFrameExoBlock((int) $config->get("max-portal-height"), (int) $config->get("max-portal-width")), $frame_block);
		self::register(new PortalExoBlock(), VanillaBlocks::NETHER_PORTAL());
	}

	public static function register(ExoBlock $exo_block, Block $block) : void{
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