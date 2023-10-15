<?php

namespace Sald;

use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Query\SimpleSelectQuery;

class Sald {

	public static function get(?Configuration $config = null): Connection {
		return ConnectionManager::get($config);
	}

	public static function select(string $className, ?Configuration $config = null): SimpleSelectQuery {
		return self::get($config)->select($className);
	}

}
