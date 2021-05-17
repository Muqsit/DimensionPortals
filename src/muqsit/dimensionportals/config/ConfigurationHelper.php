<?php

declare(strict_types=1);

namespace muqsit\dimensionportals\config;

final class ConfigurationHelper{

	/**
	 * @param mixed[] $data
	 * @param string $key
	 * @return mixed
	 *
	 * @phpstan-param array<string, mixed> $data
	 * @phpstan-return mixed
	 */
	public static function read(array &$data, string $key){
		if(!isset($data[$key])){
			throw new BadConfigurationException("Cannot find required key '{$key}'");
		}

		$value = $data[$key];
		unset($data[$key]);
		return $value;
	}

	public static function checkForUnread(array $data) : void{
		$keys = array_keys($data);
		if(count($keys) > 0){
			throw new BadConfigurationException("Unrecognized keys: '" . implode("', '", $keys) . "'");
		}
	}
}