<?php

namespace Sald\Connection;

use PDO;
use Sald\Metadata\MetadataManager;
use Sald\Metadata\TableMetadata;
use Sald\Query\SimpleSelectQuery;

class Connection extends PDO {




	public function select(string $className): SimpleSelectQuery {
		//$tableName = ->getTableName();
		return new SimpleSelectQuery($this, $this->getMetadata($className), $className);
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