<?php

namespace Sald\Connection\MultiHost;

use PDO;
use PDOException;
use Sald\Connection\Connection;
use Sald\Log;
use Sald\Exception\Db\Connection\DbConnectionException;

class MultiHostChooser {

	private const int DEFAULT_PORT = 5432;
	private const string PARAM_SERVER_TYPE = 'targetServerType';
	private const string PARAM_HOST = 'host';
	private const string PARAM_PORT = 'port';

	private const int CONNECT_TIMEOUT = 2;

	private TargetServerTypeValues $targetServerType = TargetServerTypeValues::ANY;

	private string $driver;
	private array $dsnValues = [];

	public function __construct(readonly string          $dsn,
								private readonly ?string $username = null,
								private readonly ?string $password = null,
								private readonly ?array  $options = null,
								private readonly ?string $schema = null) {
		$this->initDsn($dsn);
		if (isset($this->dsnValues[self::PARAM_SERVER_TYPE])) {
			$this->targetServerType = TargetServerTypeValues::from($this->dsnValues[self::PARAM_SERVER_TYPE]);
			unset($this->dsnValues[self::PARAM_SERVER_TYPE]);
		}
	}

	public function getConnection(): Connection {
		return $this->selectHost();
	}

	private function initDsn(string $dsn): void {
		list($driverString, $dsnValueString) = explode(':', $dsn, 2);
		if (!isset($dsnValueString)) {
			throw new PDOException(sprintf('Database driver is missing from dsn %s.', $dsn));
		}
		$this->driver = $driverString;

		foreach (explode(';', $dsnValueString) as $section) {
			$nameVal = explode('=', $section, 2);
			$this->dsnValues[$nameVal[0]] = $nameVal[1] ?? null;
		}
		Log::debug(sprintf('Parsed dsn string with properties (%s)', join(', ', array_keys($this->dsnValues))));
	}

	private function selectHost(): Connection {
		$basePort = $conn[self::PARAM_PORT] ?? self::DEFAULT_PORT;

		$conn = $this->dsnValues;
		$hosts = explode(',', $this->dsnValues[self::PARAM_HOST]);

		$testOptions = $this->options;
		$testOptions[PDO::ATTR_TIMEOUT] = self::CONNECT_TIMEOUT;

		$suitableConnections = [];

		Log::debug(sprintf('Selecting 1 host (%s) from %d hosts', $this->targetServerType->name, count($hosts)));
		foreach ($hosts as $host) {
			$hostParts = explode(':', $host, 2);

			$conn[self::PARAM_HOST] = trim($hostParts[0]);
			$conn[self::PARAM_PORT] = trim($hostParts[1] ?? $basePort);
			$dsn = $this->createDsn($this->driver, $conn);

			try {
				$test = new Connection($dsn, $this->username, $this->password, $testOptions, $this->schema);
			} catch (\PDOException $e) {
				Log::info(sprintf('Connection to %s failed: %s', $dsn, $e->getMessage()));
				// @todo log
				continue;
			}
			if ($this->targetServerType === TargetServerTypeValues::ANY) {
				Log::debug(sprintf('Using %s, as any server type is allowed.', $dsn));
				return $test;
			}

			$result = $test->query('show transaction_read_only', PDO::FETCH_ASSOC)->fetch();
			if ($result['transaction_read_only'] === 'off') {
				Log::debug(sprintf('Connection to %s fully operational (primary).', $dsn));
				// writable (= primary)
				if (in_array($this->targetServerType, [TargetServerTypeValues::PRIMARY, TargetServerTypeValues::PREFER_PRIMARY])) {
					Log::info(sprintf('Using primary host %s.', $dsn));
					return $test;
				} elseif ($this->targetServerType === TargetServerTypeValues::PREFER_SECONDARY) {
					Log::debug(sprintf('Keeping primary %s as one of the suitable connections.', $dsn));
					$suitableConnections[] = $test;
				}
			} else {
				Log::info(sprintf('Connection to %s is in readonly mode (secondary).', $dsn));
				// secondary
				if (in_array($this->targetServerType, [TargetServerTypeValues::SECONDARY, TargetServerTypeValues::PREFER_SECONDARY])) {
					Log::info(sprintf('Using secondary host %s.', $dsn));
					return $test;
				} elseif ($this->targetServerType === TargetServerTypeValues::PREFER_PRIMARY) {
					Log::debug(sprintf('Keeping secondary %s as one of the suitable connections.', $dsn));
					$suitableConnections[] = $test;
				}
			}
		}

		if (!empty($suitableConnections)) {
			Log::info('Using the first suitable connection that was inspected earlier.');
			return $suitableConnections[0];
		}
		throw new DbConnectionException(sprintf('No suitable database hosts are available for target type %s', $this->targetServerType->value));
	}

	private function createDsn(string $driver, array $dsnValues): string {
		return sprintf('%s:%s', $driver, join(';', array_map(fn ($v, $k) => $k . '=' . $v, $dsnValues, array_keys($dsnValues))));
	}

}
