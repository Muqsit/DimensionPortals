<?php

declare(strict_types=1);

namespace muqsit\netherportal\exoblock;

use muqsit\netherportal\Loader;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\VanillaBlocks;

final class ExoBlockFactory{

	/** @var ExoBlock[] */
	private static $blocks = [];

	public static function init(Loader $loader) : void{
		$loader->getServer()->getPluginManager()->registerEvents(new ExoBlockEventHandler(), $loader);

		self::register(new ObsidianExoBlock(30, 30), VanillaBlocks::OBSIDIAN());
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