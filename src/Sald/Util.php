<?php

namespace Sald;

class Util {

	/**
	 * In-place conversion of an array into a multidimensional array, with all members indexed by the specified
	 * field value.
	 *
	 * @param array &$source The source array with all elements
	 * @param string $fieldName The field name to use as index
	 */
	public static function indexByField(array &$source, string $fieldName): void {
		foreach ($source as $idx => $value) {
			$key = is_array($value) ? $value[$fieldName] : $value->$fieldName;
			if (is_numeric($key)) $key = '$$_' . $key; // @ todo better solutions for already existing keys
			$source[$key][] = $value;
			unset($source[$idx]);
		}
		foreach ($source as $idx => $value) {
			if (str_starts_with($idx, '$$_')) {
				$source[substr($idx, 3)] = $value;
				unset($source[$idx]);
			}
		}
	}

	public static function getUniqueValues(array $source, string $fieldName): array {
		$result = [];
		foreach ($source as $value) {
			$key = is_array($value) ? $value[$fieldName] : $value->$fieldName;
			if ($key !== null) $result[$key] = true;
		}
		return array_keys($result);
	}

}
