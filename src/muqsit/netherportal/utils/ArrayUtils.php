<?php

declare(strict_types=1);

namespace muqsit\netherportal\utils;

use Closure;

final class ArrayUtils{

	public static function firstOrDefault(array $array, Closure $condition, $fallback = null){
		foreach($array as $index => $element){
			if($condition($index, $element)){
				return $element;
			}
		}

		return $fallback;
	}
}