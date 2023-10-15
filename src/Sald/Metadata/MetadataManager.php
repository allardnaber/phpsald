<?php

namespace Sald\Metadata;

class MetadataManager {

	private static array $metadata = [];

	public static function getTable(string $className): TableMetadata {
		if (!isset(self::$metadata[$className])) {
			self::$metadata[$className] = new TableMetadata($className);
		}
		return self::$metadata[$className];
	}
}