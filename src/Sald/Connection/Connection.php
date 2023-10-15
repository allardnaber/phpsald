<?php

namespace Sald\Connection;

use PDO;
use PDOStatement;
use Sald\Entities\Entity;
use Sald\Exception\RecordNotFoundException;
use Sald\Metadata\MetadataManager;
use Sald\Metadata\TableMetadata;
use Sald\Query\EntityQueryFactory;
use Sald\Query\SimpleDeleteQuery;
use Sald\Query\SimpleInsertQuery;
use Sald\Query\SimpleSelectQuery;
use Sald\Query\SimpleUpdateQuery;

class Connection extends PDO {

	public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null) {
		$options = $options ?? [];
		$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
		parent::__construct($dsn, $username, $password, $options);
	}

	public function select(string $className): SimpleSelectQuery {
		return new SimpleSelectQuery($this, $this->getMetadata($className));
	}

	public function insert(string $className): SimpleInsertQuery {
		return new SimpleInsertQuery($this, $this->getMetadata($className));
	}

	public function update(string $className): SimpleUpdateQuery {
		return new SimpleUpdateQuery($this, $this->getMetadata($className));
	}

	public function delete(string $className): SimpleDeleteQuery {
		return new SimpleDeleteQuery($this, $this->getMetadata($className));
	}

	public function insertEntity(Entity $entity): SimpleInsertQuery {
		return EntityQueryFactory::insert($this,  $this->getMetadata($entity::class), $entity);
	}
	public function updateEntity(Entity $entity): SimpleUpdateQuery {
		return EntityQueryFactory::update($this,  $this->getMetadata($entity::class), $entity);
	}

	public function deleteEntity(Entity $entity): SimpleDeleteQuery {
		return EntityQueryFactory::delete($this, $this->getMetadata($entity::class), $entity);
	}

	private function getMetadata(string $className): TableMetadata {
		return MetadataManager::getTable($className);
	}

	public function fetchAll(PDOStatement $statement, string $classname): array {
		$result = [];
		foreach ($statement->fetchAll() as $record) {
			$result[] = $this->asEntity($record, $classname);
		}
		return $result;
	}

	/**
	 * Fetches a single record, accepts precisely one record to be returned and will throw an exception otherwise.
	 * @param PDOStatement $statement The (already executed) statement for which te retrieve the result.
	 * @param string $classname The classname for the instances to return.
	 * @return Entity
	 */
	public function fetchSingle(PDOStatement $statement, string $classname): Entity {
		return $this->fetchOneRecord($statement, $classname, true);
	}

	/**
	 * Similar to fetchSingle, but returns null if no records are available and the first record if the query returns
	 * multiple records.
	 * @param PDOStatement $statement The (already executed) statement for which te retrieve the result.
	 * @param string $classname The classname for the instances to return.
	 * @return Entity|null Null if the query did not return any records, the first instance of Entity otherwise.
	 */
	public function fetchFirst(PDOStatement $statement, string $classname): ?Entity {
		return $this->fetchOneRecord($statement, $classname, false);
	}

	private function fetchOneRecord(PDOStatement $statement, string $classname, bool $strict): ?Entity {
		$result = $statement->fetch();
		if ($result === false) {
			if ($strict) {
				throw new RecordNotFoundException(
					sprintf('Required record for %s was not found.', $classname));
			} else {
				return null;
			}
		} else {
			return $this->asEntity($result, $classname);
		}
	}

	private function asEntity(array $record, string $classname): Entity {
		return new $classname($this, $record);
	}

}
