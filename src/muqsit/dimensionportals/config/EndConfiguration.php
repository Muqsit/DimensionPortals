<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class EndConfiguration{

	/**
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function fromData(array $data) : self{
		$instance = new self(
			ConfigurationHelper::readString($data, "world"),
			ConfigurationHelper::readOptional($data, "sub-worlds", []),
			ConfigurationHelper::readInt($data, "teleportation-duration", 0)
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	/**
	 * @param string $world
	 * @param string[] $sub_worlds
	 * @param int $teleportation_duration
	 */
	public function __construct(
		readonly public string $world,
		readonly public array $sub_worlds,
		readonly public int $teleportation_duration
	){}
}