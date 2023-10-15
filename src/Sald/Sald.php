<?php

namespace Sald;

use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Entities\Entity;
use Sald\Query\SimpleDeleteQuery;
use Sald\Query\SimpleInsertQuery;
use Sald\Query\SimpleSelectQuery;

class Sald {

	public static function get(?Configuration $config = null): Connection {
		return ConnectionManager::get($config);
	}

	public static function select(string $className, ?Configuration $config = null): SimpleSelectQuery {
		return self::get($config)->select($className);
	}

	public static function insert(Entity $entity, ?Configuration $config = null): SimpleInsertQuery {
		return self::get($config)->insert($entity);
	}

	public static function delete(string $className, ?Configuration $config = null): SimpleDeleteQuery {
		return self::get($config)->delete($className);
	}

}
