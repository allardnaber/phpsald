<?php

namespace Sald\Connection\MultiHost;

use Sald\Connection\Connection;

class MultiHostConnection extends Connection {

	private function __construct(
		string  $dsn,
		?string $username = null,
		?string $password = null,
		?array  $options = null,
	) {
		parent::__construct($dsn, $username, $password, $options);
	}

	public static function create(
		string  $dsn,
		?string $username = null,
		?string $password = null,
		?array  $options = null
	): Connection {
		// @todo extend so it is possible to switch hosts mid request?
		return (new MultiHostChooser($dsn, $username, $password, $options))->getConnection();
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
