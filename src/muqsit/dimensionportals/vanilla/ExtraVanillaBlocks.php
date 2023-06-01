<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\vanilla;

use pocketmine\block\Block;
use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\BlockTypeInfo;
use pocketmine\block\Transparent;
use pocketmine\utils\CloningRegistryTrait;

/**
 * @method static Transparent END_PORTAL()
 */
final class ExtraVanillaBlocks{
	use CloningRegistryTrait;

	private function __construct(){
		//NOOP
	}

	protected static function register(string $name, Block $block) : void{
		self::_registryRegister($name, $block);
	}

	/**
	 * @return Block[]
	 * @phpstan-return array<string, Block>
	 */
	public static function getAll() : array{
		//phpstan doesn't support generic traits yet :(
		/** @var Block[] $result */
		$result = self::_registryGetAll();
		return $result;
	}

	protected static function setup() : void{
		self::register("end_portal", new Transparent(new BlockIdentifier(BlockTypeIds::newId()), "End Portal", new BlockTypeInfo(BlockBreakInfo::indestructible())));
	}
}