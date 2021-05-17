<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class EndConfiguration{

	/**
	 * @param array $data
	 * @return self
	 *
	 * @phpstan-param array<string, mixed>
	 */
	public static function fromData(array $data) : self{
		$instance = new self(ConfigurationHelper::read($data, "world"), ConfigurationHelper::read($data, "teleportation-duration"));
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	private string $world;
	private int $teleportation_duration;

	public function __construct(string $world, int $teleportation_duration){
		$this->world = $world;
		$this->teleportation_duration = $teleportation_duration;
	}

	public function getWorld() : string{
		return $this->world;
	}

	public function getTeleportationDuration() : int{
		return $this->teleportation_duration;
	}
}