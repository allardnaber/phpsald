<?php

namespace Sald;

use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Query\SimpleDeleteQuery;
use Sald\Query\SimpleInsertQuery;
use Sald\Query\SimpleSelectQuery;
use Sald\Query\SimpleUpdateQuery;

class Sald {

	public static function get(?Configuration $config = null): Connection {
		return ConnectionManager::get($config);
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @param Configuration|null $config
	 * @return SimpleSelectQuery<T>
	 */
	public static function select(string $className, ?Configuration $config = null): SimpleSelectQuery {
		return self::get($config)->select($className);
	}

	public static function insert(string $className, ?Configuration $config = null): SimpleInsertQuery {
		return self::get($config)->insert($className);
	}

	public static function update(string $className, ?Configuration $config = null): SimpleUpdateQuery {
		return self::get($config)->update($className);
	}

	public static function delete(string $className, ?Configuration $config = null): SimpleDeleteQuery {
		return self::get($config)->delete($className);
	}

}
