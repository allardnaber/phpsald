<?php

namespace Sald\Connection;

use PDOException;
use Sald\Connection\MultiHost\MultiHostConnection;
use Sald\Exception\AmbiguousConnectionException;
use Sald\Exception\Converter\DbErrorHandler;
use Sald\Exception\NoConnectionException;

class ConnectionManager {

	/**
	 * @var Connection[]
	 */
	private static array $connectionMap = [];

	public static function get(?Configuration $config = null): Connection {
		if ($config === null) {
			$config = ConfigurationManager::getDefault();
		}
		if ($config === null) {
			if (count(self::$connectionMap) === 0) {
				throw new NoConnectionException(
					'Connection config cannot be left empty as no connections are available yet.');
			} elseif (count(self::$connectionMap) !== 1) {
				throw new AmbiguousConnectionException(
					'Connection config cannot be left empty as multiple connections are available.');
			} else {
				return array_values(self::$connectionMap)[0];
			}
		}

		if (!isset(self::$connectionMap[$config->getChecksum()])) {
			self::$connectionMap[$config->getChecksum()] = self::createConnection($config);
		}
		return self::$connectionMap[$config->getChecksum()];
	}

	/**
	 * Method to create a Sald database Connection based on a configuration. It allows for single or multi-host connections.
	 * pgsql:port=5432;host=127.0.0.1;dbname=database;sslmode=allow;gssencmode=disable;
	 * pgsql:port=5432;host=db1.example.org,db2.example.org;dbname=database;sslmode=allow;gssencmode=disable;
	 * pgsql:host=db1.example.org:5432,db2.example.org:5400;dbname=database;sslmode=allow;gssencmode=disable;
	 */
	private static function createConnection(Configuration $config): Connection {
		try {
			if ($config->getDsn()->isMultiHost()) {
				$config->getLogger()?->debug(sprintf('Using multi host chooser for DSN %s.', $config->getDsn()));
				return MultiHostConnection::create($config);
			} else {
				return new Connection($config);
			}
		} catch (PDOException $e) {
			throw DbErrorHandler::getDbException($e, $config->getDsn()->getDriver());
		}
	}
}