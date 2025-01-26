<?php

declare(strict_types=1);

namespace muqsit\dimensionportals;

use pocketmine\block\VanillaBlocks;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\world\BlockTransaction;
use pocketmine\world\World;
use SplQueue;

final class Utils{

	/**
	 * @param World $world
	 * @param int $find_block_id
	 * @param Vector3 $origin
	 * @param list<value-of<Facing::ALL>> $check_sides
	 * @return BlockTransaction|null
	 */
	public static function removeTouchingBlocks(World $world, int $find_block_id, Vector3 $origin, array $check_sides) : ?BlockTransaction{
		$visits = new SplQueue();
		$visits->enqueue($origin);
		$air = VanillaBlocks::AIR();
		$transaction = new BlockTransaction($world);
		$filled = 0;
		while(!$visits->isEmpty()){
			/** @var Vector3 $coordinates */
			$coordinates = $visits->dequeue();
			if($transaction->fetchBlockAt($coordinates->x, $coordinates->y, $coordinates->z)->getTypeId() !== $find_block_id){
				continue;
			}
			$transaction->addBlockAt($coordinates->x, $coordinates->y, $coordinates->z, $air);
			$filled++;
			foreach($check_sides as $side){
				$visits->enqueue($coordinates->getSide($side));
			}
		}
		return $filled > 0 ? $transaction : null;
	}
}