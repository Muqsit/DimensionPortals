<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class Configuration{

	/**
	 * @param array $data
	 * @return self
	 *
	 * @phpstan-param array<string, mixed>
	 */
	public static function fromData(array $data) : self{
		$overworld = OverworldConfiguration::fromData(ConfigurationHelper::read($data, "overworld"));
		$nether = NetherConfiguration::fromData(ConfigurationHelper::read($data, "nether"));
		$end = EndConfiguration::fromData(ConfigurationHelper::read($data, "end"));
		ConfigurationHelper::checkForUnread($data);
		return new self($overworld, $nether, $end);
	}

	private OverworldConfiguration $overworld;
	private NetherConfiguration $nether;
	private EndConfiguration $end;

	public function __construct(OverworldConfiguration $overworld, NetherConfiguration $nether, EndConfiguration $end){
		$this->overworld = $overworld;
		$this->nether = $nether;
		$this->end = $end;
	}

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