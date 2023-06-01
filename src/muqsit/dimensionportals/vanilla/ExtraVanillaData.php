<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\vanilla;

use pocketmine\block\Block;
use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\data\bedrock\item\ItemTypeNames;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\scheduler\AsyncPool;
use pocketmine\scheduler\AsyncTask;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;

final class ExtraVanillaData{

	public static function registerOnAllThreads(AsyncPool $pool) : void{
		self::registerOnCurrentThread();
		$pool->addWorkerStartHook(function(int $worker) use($pool) : void{
			$pool->submitTaskToWorker(new class extends AsyncTask{
				public function onRun() : void{
					ExtraVanillaData::registerOnCurrentThread();
				}
			}, $worker);
		});
	}

	public static function registerOnCurrentThread() : void{
		self::registerBlocks();
		self::registerItems();
	}

	private static function registerBlocks() : void{
		self::registerSimpleBlock(BlockTypeNames::END_PORTAL, ExtraVanillaBlocks::END_PORTAL(), ["end_portal"]);
	}

	private static function registerItems() : void{
		self::registerSimpleItem(ItemTypeNames::ENDER_EYE, ExtraVanillaItems::ENDER_EYE(), ["ender_eye"]);
	}

	/**
	 * @param string[] $stringToItemParserNames
	 */
	private static function registerSimpleBlock(string $id, Block $block, array $stringToItemParserNames) : void{
		RuntimeBlockStateRegistry::getInstance()->register($block);

		GlobalBlockStateHandlers::getDeserializer()->mapSimple($id, fn() => clone $block);
		GlobalBlockStateHandlers::getSerializer()->mapSimple($block, $id);

		foreach($stringToItemParserNames as $name){
			StringToItemParser::getInstance()->registerBlock($name, fn() => clone $block);
		}
	}

	/**
	 * @param string[] $stringToItemParserNames
	 */
	private static function registerSimpleItem(string $id, Item $item, array $stringToItemParserNames) : void{
		GlobalItemDataHandlers::getDeserializer()->map($id, fn() => clone $item);
		GlobalItemDataHandlers::getSerializer()->map($item, fn() => new SavedItemData($id));

		foreach($stringToItemParserNames as $name){
			StringToItemParser::getInstance()->register($name, fn() => clone $item);
		}
	}
}