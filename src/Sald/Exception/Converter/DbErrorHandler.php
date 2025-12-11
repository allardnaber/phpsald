<?php

namespace Sald\Exception\Converter;

use PDO;
use PDOException;
use Sald\Connection\Connection;
use Sald\Exception\Db\DbException;

class DbErrorHandler {

	private const DRIVER_CONVERTERS = [
		'pgsql' => PgsqlErrorConverter::class
	];

	public static function getDbExceptionWithConnection(PDOException $e, Connection $connection): DbException {
		$driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		return self::getDbException($e, $driver);
	}

	public static function getDbException(PDOException $e, string $driver): DbException {
		if (($converterClass = self::DRIVER_CONVERTERS[$driver] ?? null) !== null) {
			$instance = new $converterClass();
			assert($instance instanceof ErrorConverter);
			throw $instance->convert(
				$e,
				$e->errorInfo[0] ?? null,
				$e->errorInfo[1] ?? null,
				$e->errorInfo[2] ?? null
			);
		} else {
			throw DbException::fromException($e);
		}
	}

}
