<?php

namespace Sald\Metadata;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Sald\Attributes\Column;
use Sald\Attributes\Id;
use Sald\Attributes\OneToMany;
use Sald\Attributes\Table;
use Sald\Attributes\Transient;
use Sald\Exception\ClassNotFoundException;

class MetadataManager {

	private static array $metadata = [];
	// @todo: make configuration option
	private const CONVERT_TO_SNAKE_CASE = true;

	public static function getTable(string $className): TableMetadata {
		if (!isset(self::$metadata[$className])) {
			self::$metadata[$className] = self::fetchTableMetadata($className);
		}
		return self::$metadata[$className];
	}

	private static function fetchTableMetadata(string $className): TableMetadata {
		try {
			$reflection = new ReflectionClass($className);
			$result = new TableMetadata($className);

			$tableDef = self::getFirstReflectionAttribute($reflection, Table::class);
			if ($tableDef instanceof Table) {
				$result->setNameOverride($tableDef->getName());
			} else {
				$name = $reflection->getShortName();
				$result->setNameOverride(self::CONVERT_TO_SNAKE_CASE ? Converter::toSnakeCase($name) : $name);
			}

			$columns = self::discoverColumns($reflection);
			$result->setColumns($columns);
			$result->setIdColumns(self::findIdColumns($columns));
			return $result;

		} catch (ReflectionException $e) {
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
				$result[] = $column->getRealObjectName();
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
			if (empty($property->getAttributes(Transient::class))) {
				$result[$property->getName()] = self::getColumn($property);
			}
		}
		return $result;
	}

	private static function getColumn(ReflectionProperty $reflection): ColumnMetadata {
		$result = new ColumnMetadata($reflection->getName(), $reflection->getType());
		$relation = self::getFirstReflectionAttribute($reflection, OneToMany::class);
		if ($relation instanceof OneToMany) {
			$result->setOneToMany($relation);
		}
		$idAttribute = self::getFirstReflectionAttribute($reflection, Id::class);
		if ($idAttribute instanceof Id) {
			$result->applyIdAttribute($idAttribute);
		}
		$columnAttribute = self::getFirstReflectionAttribute($reflection, Column::class);
		if ($columnAttribute instanceof Column) {
			$result->applyColumnAttribute($columnAttribute);
		} elseif (self::CONVERT_TO_SNAKE_CASE) {
			$result->setNameOverride(Converter::toSnakeCase($reflection->getName()));
		}
		return $result;
	}
}
