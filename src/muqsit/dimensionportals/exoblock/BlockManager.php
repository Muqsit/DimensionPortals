<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\exoblock;

use muqsit\dimensionportals\BadConfigurationException;
use muqsit\dimensionportals\Loader;
use muqsit\dimensionportals\vanilla\ExtraVanillaBlocks;
use muqsit\dimensionportals\vanilla\ExtraVanillaItems;
use muqsit\dimensionportals\WorldManager;
use pocketmine\block\Block;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use function array_combine;
use function array_keys;
use function array_map;
use function array_values;
use function gettype;
use function is_int;
use function is_string;

final class BlockManager{

	/** @var array<WorldManager::DIMENSION_*, Block> */
	public array $portal_block_dimensions;

	/** @var array<int, WorldManager::DIMENSION_*> */
	public array $portal_block_dimensions_reverse_mapping;

	public Block $nether_portal_frame_block;
	public int $nether_portal_max_width;
	public int $nether_portal_max_height;
	public int $nether_portal_tp_duration;

	public Block $end_portal_frame_unactivated_block;
	public Block $end_portal_frame_activated_block;
	public Item $end_portal_frame_activation_item;
	public int $end_portal_tp_duration;

	public function __construct(Loader $plugin){
		$config = $plugin->getConfig();

		$nether_portal_width = $config->getNested("nether-portal.max-width");
		is_int($nether_portal_width) || throw new BadConfigurationException("nether-portal.max-width: value must be integer, got: " . gettype($nether_portal_width));

		$nether_portal_height = $config->getNested("nether-portal.max-height");
		is_int($nether_portal_height) || throw new BadConfigurationException("nether-portal.max-height: value must be integer, got: " . gettype($nether_portal_height));

		$nether_portal_tp_duration = $config->getNested("nether-portal.teleportation-duration");
		is_int($nether_portal_tp_duration) || throw new BadConfigurationException("nether-portal.teleportation-duration: value must be integer, got: " . gettype($nether_portal_tp_duration));

		$end_portal_tp_duration = $config->getNested("end-portal.teleportation-duration");
		is_int($end_portal_tp_duration) || throw new BadConfigurationException("end-portal.teleportation-duration: value must be integer, got: " . gettype($end_portal_tp_duration));

		$this->portal_block_dimensions = [
			WorldManager::DIMENSION_END => ExtraVanillaBlocks::END_PORTAL(),
			WorldManager::DIMENSION_NETHER => VanillaBlocks::NETHER_PORTAL()
		];
		$this->portal_block_dimensions_reverse_mapping = array_combine(array_map(static fn($b) => $b->getTypeId(), array_values($this->portal_block_dimensions)), array_keys($this->portal_block_dimensions));
		$this->nether_portal_max_width = $nether_portal_width;
		$this->nether_portal_max_height = $nether_portal_height;
		$this->nether_portal_tp_duration = $nether_portal_tp_duration;
		$this->end_portal_tp_duration = $end_portal_tp_duration;
		$this->end_portal_frame_activation_item = ExtraVanillaItems::ENDER_EYE();
		$this->end_portal_frame_unactivated_block = VanillaBlocks::END_PORTAL_FRAME()->setEye(false);
		$this->end_portal_frame_activated_block = VanillaBlocks::END_PORTAL_FRAME()->setEye(true);
	}

	public function init(Loader $plugin) : void{
		$config = $plugin->getConfig();
		$nether_portal_frame = $config->getNested("nether-portal.frame-block");
		is_string($nether_portal_frame) || throw new BadConfigurationException("nether-portal.frame-block: value must be string, got: " . gettype($nether_portal_frame));

		$nether_frame_block = StringToItemParser::getInstance()->parse($nether_portal_frame)?->getBlock();
		($nether_frame_block !== null && $nether_frame_block->getTypeId() !== BlockTypeIds::AIR) || throw new BadConfigurationException("nether-portal.frame-block: invalid block type specified: {$nether_portal_frame}");
		$this->nether_portal_frame_block = $nether_frame_block;

		$plugin->getServer()->getPluginManager()->registerEvents(new BlockListener($this, $plugin->getPlayerManager(), $plugin->getWorldManager()), $plugin);
	}
}