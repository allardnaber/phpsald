<?php

namespace Sald\Query;

use Sald\Connection\Connection;
use Sald\Entities\Entity;
use Sald\Metadata\TableMetadata;

/**
 * Convenience class to build insert/update/delete statements to work with entities.
 */
class EntityQueryFactory {

	public static function insert(Connection $connection, TableMetadata $metadata, Entity $entity): SimpleInsertQuery {
		return self::upsert(true, $connection, $metadata, $entity);
	}

	public static function update(Connection $connection, TableMetadata $metadata, Entity $entity): SimpleUpdateQuery {
		return self::upsert(false, $connection, $metadata, $entity);
	}

	public static function delete(Connection $connection, TableMetadata $metadata, Entity $entity): SimpleDeleteQuery {
		$result = new SimpleDeleteQuery($connection, $metadata);
		self::addIdWhereToQuery($metadata, $entity, $result);
		return $result;
	}

	private static function upsert(bool $isInsert, Connection $connection, TableMetadata $metadata, Entity $entity):
			SimpleInsertQuery|SimpleUpdateQuery {
		if ($isInsert) {
			$baseQuery = new SimpleInsertQuery($connection, $metadata);
		} else {
			$baseQuery = new SimpleUpdateQuery($connection, $metadata);
			self::addIdWhereToQuery($metadata, $entity, $baseQuery);
		}

		$columns = $metadata->getColumns();
		$dbFields = array_filter($entity->getDirtyFields(), fn(string $field) => $columns[$field]?->isEditable());

		foreach ($dbFields as $field) {
			$expr = $entity->getExpression($field);
			$baseQuery->set($columns[$field]?->getColumnName() ?? $field, $expr ?? $entity->$field);
		}
		return $baseQuery;
	}

	private static function addIdWhereToQuery(TableMetadata $metadata, Entity $entity, AbstractQuery $query): void {
		$idKey = [];
		foreach ($metadata->getIdColumns() as $key) {
			$colName = $metadata->getColumn($key)->getColumnName();
			$idKey[$colName] = $entity->$key;
		}
		$query->whereId($idKey);
	}
}
