<?php

namespace Sald\Connection;

use PDOException;
use Sald\Exception\Converter\DbErrorHandler;
use Sald\Exception\Db\Connection\DbConnectionException;

class Configuration {

	private string $dsn, $username, $password;
	private ?array $options;

	private string $checksum;

	public function __construct(string $dsn, string $username, string $password, ?array $options = null) {
		$this->dsn = $dsn;
		$this->username = $username;
		$this->password = $password;
		$this->options = $options;
		$this->checksum = md5($dsn . $username . $password . json_encode($options ?? []));
	}

	public function createConnection(): Connection {
		try {
			return new Connection($this->dsn, $this->username, $this->password, $this->options);
		} catch (PDOException $e) {
			$driverParts = explode(':', $this->dsn, 2);
			if (count($driverParts) > 1) {
				throw DbErrorHandler::getDbException($e, $driverParts[0]);
			} else {
				throw DbConnectionException::fromException($e);
			}
		}
	}

	public function getChecksum(): string {
		return $this->checksum;
	}

}