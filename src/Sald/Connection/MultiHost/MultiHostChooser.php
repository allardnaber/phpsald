<?php

namespace Sald\Connection\MultiHost;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\Dsn;
use Sald\Exception\Db\Connection\DbConnectionException;

class MultiHostChooser {

	private const string NO_CONNECTIONS_CACHE_VALUE = '*no connection*';
	private const int DEFAULT_HOST_CHECK_CONNECT_TIMEOUT = 2;
	private const int DEFAULT_SERVER_STATUS_TTL = 60;
	private ?LoggerInterface $logger;
	private HostCache $cache;

	/**
	 * @var ServerStatus[]
	 */
	private array $serverStatuses = [];

	/**
	 * Additional connections, used to fallback in case of PREFER_PRIMARY or PREFER_SECONDARY and no primary or
	 * secondary connection is available.
	 * @var Connection[]
	 */
	private array $connections = [];

	private ?Connection $cachedConnection = null;

	public function __construct(private readonly Configuration $config) {
		$this->logger = $config->getLogger() ?? null;
		$this->initFromCache();
	}

	public function getConnection(): Connection {
		$connection = $this->cachedConnection !== null ? $this->cachedConnection : $this->selectHost();
		if ($connection !== null) return $connection;

		throw new DbConnectionException(sprintf(
			'No suitable database hosts are available for target type %s',
			$this->config->getDsn()->getTargetServerType()->value)
		);
	}

	private function selectHost(): ?Connection {
		$testTimeout = $this->config->getHostCheckTimeout() ?? self::DEFAULT_HOST_CHECK_CONNECT_TIMEOUT;
		$targetServerType = $this->config->getDsn()->getTargetServerType();

		$basePort = $this->config->getDsn()->getElement(Dsn::ELEM_PORT) ?? Dsn::DEFAULT_PORT;
		$hosts = explode(',', $this->config->getDsn()->getElement(Dsn::ELEM_HOST));

		$this->logger?->debug(sprintf('Selecting 1 (%s) host from %d hosts', $targetServerType->name, count($hosts)));
		foreach ($hosts as $host) {
			$hostParts = explode(':', $host, 2);

			$dsn = clone $this->config->getDsn();
			$dsn->setElement(Dsn::ELEM_HOST, trim($hostParts[0]));
			$dsn->setElement(Dsn::ELEM_PORT, trim($hostParts[1] ?? $basePort));

			$dsnString = (string) $dsn;
			$connection = $this->createConnectionAndKeepStatus($dsnString, $testTimeout);
			if ($connection === null) continue;

			if ($targetServerType === TargetServerType::ANY) {
				$this->logger?->debug(sprintf('Using %s, as any server type is allowed.', $dsnString));
				$this->cache->saveHostForConfiguration($this->config->getChecksum(), $dsnString);
				return $connection;
			}

			$status = $this->serverStatuses[$dsnString];
			if ($status === ServerStatus::PRIMARY) {
				$this->logger?->debug(sprintf('Connection to %s fully operational (primary).', $dsnString));
				if (in_array($targetServerType, [TargetServerType::PRIMARY, TargetServerType::PREFER_PRIMARY])) {
					$this->logger?->info(sprintf('Using primary host %s.', $dsnString));
					$this->cache->saveHostForConfiguration($this->config->getChecksum(), $dsnString);
					return $connection;
				} elseif ($targetServerType === TargetServerType::PREFER_SECONDARY) {
					$this->connections[$dsnString] = $connection;
					$this->logger?->debug(sprintf('Skipping primary %s for now as secondary is preferred.', $dsnString));
				}
			} else { // ServerStatus::SECONDARY
				$this->logger?->info(sprintf('Connection to %s is in readonly mode (secondary).', $dsnString));
				if (in_array($targetServerType, [TargetServerType::SECONDARY, TargetServerType::PREFER_SECONDARY])) {
					$this->logger?->info(sprintf('Using secondary host %s.', $dsnString));
					$this->cache->saveHostForConfiguration($this->config->getChecksum(), $dsnString);
					return $connection;
				} elseif ($targetServerType === TargetServerType::PREFER_PRIMARY) {
					$this->connections[$dsnString] = $connection;
					$this->logger?->debug(sprintf('Skipping secondary %s for now as primary is preferred.', $dsnString));
				}
			}
		}

		if (in_array($targetServerType, [TargetServerType::PREFER_PRIMARY, TargetServerType::PREFER_SECONDARY])) {
			foreach ($this->serverStatuses as $dsnString => $status) {
				if (
					($status === ServerStatus::PRIMARY && $targetServerType === TargetServerType::PREFER_SECONDARY) ||
					($status === ServerStatus::SECONDARY && $targetServerType === TargetServerType::PREFER_PRIMARY)
				) {
					$this->logger?->info(sprintf('Falling back to %s host %s.', $status->name, $dsnString));
					$this->cache->saveHostForConfiguration($this->config->getChecksum(), $dsnString);
					$result = $this->connections[$dsnString];
					$this->connections = [];
					return $result;
				}
			}
		}

		// @todo also cache connection unavailability?
		//$this->cache->saveHostForConfiguration($this->config->getChecksum(), self::NO_CONNECTIONS_CACHE_VALUE);
		$this->noConnectionAvailable();
	}

	private function initFromCache(): void {
		$this->cache = new HostCache($this->config->getHostStatusTtl() ?? self::DEFAULT_SERVER_STATUS_TTL);
		$cachedDsn = $this->cache->getHostForConfiguration($this->config->getChecksum());
		if ($cachedDsn === null) return;
		if ($cachedDsn === self::NO_CONNECTIONS_CACHE_VALUE) $this->noConnectionAvailable();

		$this->cachedConnection = $this->createConnectionAndKeepStatus($cachedDsn);
		if ($this->cachedConnection === null) {
			$this->cache->deleteHostForConfiguration($this->config->getChecksum());
		} else {
			$this->logger?->info(sprintf('Retrieved connection %s from cache.', $cachedDsn));
		}
	}

	private function createConnectionAndKeepStatus(string $dsn, ?int $timeout = null): ?Connection {
		$connConfig = clone $this->config;
		$connConfig->setDsn($dsn);
		if ($timeout !== null) {
			$options = $connConfig->getOptions();
			$options[PDO::ATTR_TIMEOUT] = $timeout;
			$connConfig->setOptions($options);
		}
		try {
			$result = new Connection($connConfig);
			$this->serverStatuses[$dsn] = $this->fetchServerStatus($result);
			return $result;
		} catch (PDOException $e) {
			$this->serverStatuses[$dsn] = ServerStatus::UNAVAILABLE;
			$this->logger?->info(sprintf('Connection to %s unavailable: %s', $connConfig->getDsn(), $e->getMessage()));
			return null;
		}
	}

	private function fetchServerStatus(Connection $connection): ServerStatus {
		$result = $connection->query('show transaction_read_only', PDO::FETCH_ASSOC)->fetch();
		return $result['transaction_read_only'] === 'off' ? ServerStatus::PRIMARY : ServerStatus::SECONDARY;
	}

	/**
	 * Throws an exception if no suitable connection could be found, potentially also if it came from cache;
	 * @return void
	 */
	private function noConnectionAvailable(): void {
		throw new DbConnectionException(sprintf(
				'No suitable database hosts are available for target type %s',
				$this->config->getDsn()->getTargetServerType()->value)
		);
	}

}
