<?php

namespace Sald\Connection;

use PDO;
use Sald\Metadata\MetadataManager;
use Sald\Metadata\TableMetadata;
use Sald\Query\SimpleDeleteQuery;
use Sald\Query\SimpleSelectQuery;

class Connection extends PDO {




	public function select(string $className): SimpleSelectQuery {
		return new SimpleSelectQuery($this, $this->getMetadata($className));
	}

	public function delete(string $className): SimpleDeleteQuery {
		return new SimpleDeleteQuery($this, $this->getMetadata($className));
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