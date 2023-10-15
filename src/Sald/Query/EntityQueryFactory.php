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
		$result = new SimpleInsertQuery($connection, $metadata);
		$insertFields = array_intersect($entity->getDirtyFields(), $metadata->getEditableFields());

		foreach ($insertFields as $field) {
			$expr = $entity->getExpression($field);
			$result->set($field, $expr ?? $entity->$field);
		}
		return $result;
	}

	public static function update(Connection $connection, TableMetadata $metadata, Entity $entity): SimpleUpdateQuery {
		$result = new SimpleUpdateQuery($connection, $metadata);
		$idColumn = $metadata->getIdColumnName();
		$result->whereLiteral($idColumn . '=:' . $idColumn);
		$result->parameter($idColumn, $entity->$idColumn);

		$updateFields = array_intersect($entity->getDirtyFields(), $metadata->getEditableFields());

		foreach ($updateFields as $field) {
			$expr = $entity->getExpression($field);
			$result->set($field, $expr ?? $entity->$field);
		}
		return $result;
	}

	public static function delete(Connection $connection, TableMetadata $metadata, Entity $entity): SimpleDeleteQuery {
		$result = new SimpleDeleteQuery($connection, $metadata);
		$idColumn = $metadata->getIdColumnName();
		$result->whereLiteral($idColumn . '=:' . $idColumn);
		$result->parameter($idColumn, $entity->$idColumn);
		return $result;
	}
}
