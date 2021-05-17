<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class NetherPortalConfiguration{

	/**
	 * @param array $data
	 * @return self
	 *
	 * @phpstan-param array<string, mixed> $data
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

	private string $frame_block;
	private int $max_width;
	private int $max_height;

	public function __construct(string $frame_block, int $max_width, int $max_height){
		$this->frame_block = $frame_block;
		$this->max_width = $max_width;
		$this->max_height = $max_height;
	}

	public function getFrameBlock() : string{
		return $this->frame_block;
	}

	public function getMaxWidth() : int{
		return $this->max_width;
	}

	public function getMaxHeight() : int{
		return $this->max_height;
	}
}