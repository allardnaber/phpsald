<?php

namespace Sald\Connection;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PDOException;
use Sald\Exception\Converter\DbErrorHandler;
use Sald\Exception\Db\Connection\DbConnectionException;
use Sald\Log;
use SensitiveParameter;

readonly class Configuration {

	private string $checksum;

	public function __construct(
		private string $dsn,
		private string $username,
		#[SensitiveParameter] private string $password,
		private ?array $options = null,
		private ?string $schema = null) {

		$this->checksum = md5($dsn . $username . $password . json_encode($options ?? []) . $schema ?? '');
		if (!Log::hasLogger()) {
			Log::setLogger(
				new Logger(
					'PHPsald',
					[ new StreamHandler('php://stdout') ]
				)
			);
		}
	}

	public function createConnection(): Connection {
		try {
			return ConnectionFactory::create($this->dsn, $this->username, $this->password, $this->options, $this->schema);
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