<?php

namespace Sald\Metadata;

class Converter {

	public static function toSnakeCase(string $input): string {
		return strtolower(preg_replace('/([a-z](?=[A-Z])|[A-Z](?=[A-Z][a-z]))/', '$0_', $input));
	}

	public static function testConverter(): void {
		// @todo move to proper testing
		assert(self::toSnakeCase('basic') === 'basic');
		assert(self::toSnakeCase('simpleName') === 'simple_name');
		assert(self::toSnakeCase('stillASimpleName') === 'still_a_simple_name');
		assert(self::toSnakeCase('nameWithABBR') === 'name_with_abbr');
		assert(self::toSnakeCase('nameWithABBRHalfway') === 'name_with_abbr_halfway');
	}
}
