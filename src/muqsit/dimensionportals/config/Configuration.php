<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class Configuration{

	/**
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function fromData(array $data) : self{
		$overworld = OverworldConfiguration::fromData(ConfigurationHelper::readMap($data, "overworld"));
		$nether = NetherConfiguration::fromData(ConfigurationHelper::readMap($data, "nether"));
		$end = EndConfiguration::fromData(ConfigurationHelper::readMap($data, "end"));
		ConfigurationHelper::checkForUnread($data);
		return new self($overworld, $nether, $end);
	}

	public function __construct(
		private OverworldConfiguration $overworld,
		private NetherConfiguration $nether,
		private EndConfiguration $end
	){}

	public function getOverworld() : OverworldConfiguration{
		return $this->overworld;
	}

	public function getNether() : NetherConfiguration{
		return $this->nether;
	}

	public function getEnd() : EndConfiguration{
		return $this->end;
	}
}