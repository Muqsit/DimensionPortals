<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class OverworldConfiguration{

	/**
	 * @param array $data
	 * @return self
	 *
	 * @phpstan-param array<string, mixed>
	 */
	public static function fromData(array $data) : self{
		return new self($data["world"]);
	}

	private string $world;

	public function __construct(string $world){
		$this->world = $world;
	}

	public function getWorld() : string{
		return $this->world;
	}
}