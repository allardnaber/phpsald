<?php

namespace Sald\Connection;

use Sald\Exception\AmbiguousConnectionException;
use Sald\Exception\NoConnectionException;

class ConnectionManager {

	/**
	 * @var Connection[]
	 */
	private static array $connectionMap = [];

	public static function get(?Configuration $config = null): Connection {
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
			self::$connectionMap[$config->getChecksum()] = $config->createConnection();
		}
		return self::$connectionMap[$config->getChecksum()];
	}
}