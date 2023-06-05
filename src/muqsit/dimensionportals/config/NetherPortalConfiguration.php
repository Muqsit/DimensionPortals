<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class NetherPortalConfiguration{

	/**
	 * @param array<string, mixed> $data
	 * @return self
	 */
	public static function fromData(array $data) : self{
		$instance = new self(
			ConfigurationHelper::readString($data, "frame-block"),
			ConfigurationHelper::readInt($data, "max-width", 1),
			ConfigurationHelper::readInt($data, "max-height", 1)
		);
		ConfigurationHelper::checkForUnread($data);
		return $instance;
	}

	public function __construct(
		readonly public string $frame_block,
		readonly public int $max_width,
		readonly public int $max_height
	){}
}