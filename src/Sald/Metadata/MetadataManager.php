<?php

namespace Sald\Metadata;

use ReflectionClass;
use ReflectionProperty;
use Sald\Attributes\Column;
use Sald\Attributes\Id;
use Sald\Attributes\Table;
use Sald\Exception\ClassNotFoundException;

class MetadataManager {

	private static array $metadata = [];

	public static function getTable(string $className): TableMetadata {
		if (!isset(self::$metadata[$className])) {
			self::$metadata[$className] = self::fetchTableMetadata($className);
		}
		return self::$metadata[$className];
	}

	private static function fetchTableMetadata(string $className): TableMetadata {
		try {
			$reflection = new ReflectionClass($className);
			$tableName = $reflection->getShortName();
			$tableDef = self::getFirstReflectionAttribute($reflection, Table::class);
			if ($tableDef instanceof Table) {
				$tableName = $tableDef->getName();
			}

			$result = new TableMetadata($className, $tableName);
			$columns = self::discoverColumns($reflection);
			$result->setColumns($columns);
			$result->setIdColumns(self::findIdColumns($columns));
			return $result;

		} catch (\ReflectionException $e) {
			throw new ClassNotFoundException(
				sprintf('Class %s does not exist and cannot be used as database entity', $className),
				$e->getCode(), $e);
		}
	}

	private static function getFirstReflectionAttribute(
		ReflectionClass|ReflectionProperty $reflection,
		string $attributeClassName
	): ?object {
		$attributes = $reflection->getAttributes($attributeClassName);
		return empty($attributes) ? null : $attributes[0]->newInstance();
	}

	/**
	 * @param ColumnMetadata[] $columns
	 * @return string[]
	 */
	private static function findIdColumns(array $columns): array {
		$result = [];
		foreach ($columns as $column) {
			if ($column->isIdColumn()) {
				$result[] = $column->getPropertyName();
			}
		}
		return $result;
	}

	/**
	 * @param ReflectionClass $reflection
	 * @return ColumnMetadata[]
	 */
	private static function discoverColumns(ReflectionClass $reflection): array {
		$result = [];
		foreach ($reflection->getProperties() as $property) {
			$result[$property->getName()] = self::getColumn($property);
		}
		return $result;
	}

	private static function getColumn(ReflectionProperty $reflection): ColumnMetadata {
		$result = new ColumnMetadata($reflection->getName(), $reflection->getType());
		$idAttribute = self::getFirstReflectionAttribute($reflection, Id::class);
		if ($idAttribute instanceof Id) {
			$result->applyIdAttribute($idAttribute);
		}
		$columnAttribute = self::getFirstReflectionAttribute($reflection, Column::class);
		if ($columnAttribute instanceof Column) {
			$result->applyColumnAttribute($columnAttribute);
		}
		return $result;
	}
}
