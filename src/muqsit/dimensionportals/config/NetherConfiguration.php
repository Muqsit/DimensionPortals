<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class NetherConfiguration{

	/**
	 * @param array $data
	 * @return self
	 *
	 * @phpstan-param array<string, mixed>
	 */
	public static function fromData(array $data) : self{
		$instance = new self(
			ConfigurationHelper::read($data, "world"),
			ConfigurationHelper::read($data, "teleportation-duration"),
			NetherPortalConfiguration::fromData(ConfigurationHelper::read($data, "portal"))
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	private string $world;
	private int $teleportation_duration;
	private NetherPortalConfiguration $portal;

	public function __construct(string $world, int $teleportation_duration, NetherPortalConfiguration $portal){
		$this->world = $world;
		$this->teleportation_duration = $teleportation_duration;
		$this->portal = $portal;
	}

	public function getWorld() : string{
		return $this->world;
	}

	public function getTeleportationDuration() : int{
		return $this->teleportation_duration;
	}

	public function getPortal() : NetherPortalConfiguration{
		return $this->portal;
	}
}