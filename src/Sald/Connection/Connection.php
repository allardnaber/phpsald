<?php

namespace Sald\Connection;

use PDO;
use Sald\Entities\Entity;
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

	public function fetchAllAsObjects(array $records, string $classname): array {
		$result = [];
		foreach ($records as $record) {
			$result[] = new $classname($this, $record);
		}

		return $result;
	}


	private function getMetadata(string $className): TableMetadata {
		return MetadataManager::getTable($className);
	}


}