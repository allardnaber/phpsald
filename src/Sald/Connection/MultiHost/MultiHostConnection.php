<?php

namespace Sald\Connection\MultiHost;

use Sald\Connection\Configuration;
use Sald\Connection\Connection;

class MultiHostConnection extends Connection {

	public static function create(Configuration $config): Connection {
		// @todo extend so it is possible to switch hosts mid request?
		return (new MultiHostChooser($config))->getConnection();
		//return new MultiHostConnection($chooser, $username, $password, $options);
	}



	/*public function fetchAllImpl(PDOStatement $statement): array|bool {
		try {
			return parent::fetchAllImpl($statement);
		} catch (PDOException $e) {
			throw DbErrorHandler::getDbExceptionWithConnection($e, $this);
		}
	}

	public function fetchImpl(PDOStatement $statement): mixed {
		try {
			return $statement->fetch(PDO::FETCH_ASSOC);
		}  catch (PDOException $e) {
			throw DbErrorHandler::getDbExceptionWithConnection($e, $this);
		}
	}

	public function executeImpl(PDOStatement $statement, ?array $params = null): bool {
		try {
			return $statement->execute($params);
		}  catch (PDOException $e) {
			throw DbErrorHandler::getDbExceptionWithConnection($e, $this);
		}
	}*/
}
