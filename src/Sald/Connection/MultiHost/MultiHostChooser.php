<?php

namespace Sald\Connection\MultiHost;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\Dsn;
use Sald\Exception\Db\Connection\DbConnectionException;

/**
 * Chooser for multihost DSNs. Based on the required server status a server will be selected. If APCu is available,
 * server statuses are being cached to prevent frequent rechecks. After establishing a connection it is verified to
 * still match the requirements.
 * In case the status has changed, the cache is invalidated and the switchover should be immediate.
 */
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

	private ?Connection $cachedConnection = null;

	private ?Connection $fallbackConnection = null;
	private ?string $fallbackDsn = null;

	public function __construct(private readonly Configuration $config) {
		$this->logger = $config->getLogger() ?? null;
		$this->initFromCache();
	}

	/**
	 * Gets a suitable connection that matches the required server type.
	 * @return Connection
	 */
	public function getConnection(): Connection {
		$connection = $this->cachedConnection !== null ? $this->cachedConnection : $this->selectHost();
		if ($connection !== null) return $connection;

		$this->noConnectionAvailable();
	}

	/**
	 * Called if no cached connection string is available. Flow:
	 * <ul>
	 *  <li>Connect to host. If it fails, mark as unavailable and proceed to next host.</li>
	 *  <li>If ANY server type is required, return the current connection.</li>
	 *  <li>Fetch the server status (primary if writable, secondary otherwise).</li>
	 *  <li>If exactly that status is required or preferred, return the current connection.</li>
	 *  <li>If the current status is not preferred but acceptable, save as fallback connection and proceed.</li>
	 * </ul>
	 * Finally: see if no connection has been returned, return the fallback connection if available.
	 *
	 * The resulting DSN is stored in cache (if available), so the test will not happen too frequently.
	 * @return Connection|null
	 */
	private function selectHost(): ?Connection {
		$basePort = $this->config->getDsn()->getElement(Dsn::ELEM_PORT) ?? Dsn::DEFAULT_PORT;
		$hosts = explode(',', $this->config->getDsn()->getElement(Dsn::ELEM_HOST));

		$this->logger?->debug(sprintf(
			'Selecting %s host from %d hosts',
			$this->config->getDsn()->getTargetServerType()->value, count($hosts))
		);

		foreach ($hosts as $host) {
			$hostParts = explode(':', $host, 2);

			$dsn = clone $this->config->getDsn();
			$dsn->setElement(Dsn::ELEM_HOST, trim($hostParts[0]));
			$dsn->setElement(Dsn::ELEM_PORT, trim($hostParts[1] ?? $basePort));

			if (($result = $this->testAndGetConnection($dsn)) !== null) {
				$this->fallbackConnection = null; // release potential fallback connection
				return $result;
			}
		}

		// The preferred server type is unavailable, try a fallback
		if ($this->fallbackConnection !== null) {
			$this->logger?->info(sprintf('Falling back to %s server %s.',
				$this->serverStatuses[$this->fallbackDsn]->name, $this->fallbackDsn));
			$this->cache->saveHostForConfiguration($this->config->getChecksum(), $this->fallbackDsn);
			return $this->fallbackConnection;
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
			$this->logger?->debug(sprintf('Retrieved connection %s from cache.', $cachedDsn));
		}
	}

	private function testAndGetConnection(string $dsnString): ?Connection {
		$testTimeout = $this->config->getHostCheckTimeout() ?? self::DEFAULT_HOST_CHECK_CONNECT_TIMEOUT;
		$targetServerType = $this->config->getDsn()->getTargetServerType();

		$connection = $this->createConnectionAndKeepStatus($dsnString, $testTimeout);
		if ($connection === null) return null;

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
				$this->fallbackDsn = $dsnString;
				$this->fallbackConnection = $connection;
				$this->logger?->debug(sprintf('Skipping primary %s for now as secondary is preferred.', $dsnString));
			}
		} else { // ServerStatus::SECONDARY
			$this->logger?->info(sprintf('Connection to %s is in readonly mode (secondary).', $dsnString));
			if (in_array($targetServerType, [TargetServerType::SECONDARY, TargetServerType::PREFER_SECONDARY])) {
				$this->logger?->info(sprintf('Using secondary host %s.', $dsnString));
				$this->cache->saveHostForConfiguration($this->config->getChecksum(), $dsnString);
				return $connection;
			} elseif ($targetServerType === TargetServerType::PREFER_PRIMARY) {
				$this->fallbackDsn = $dsnString;
				$this->fallbackConnection = $connection;
				$this->logger?->debug(sprintf('Skipping secondary %s for now as primary is preferred.', $dsnString));
			}
		}
		return null;
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
			$result = new Connection($connConfig, $this->config->getChecksum());
			$status =$this->fetchServerStatus($result);
			$this->serverStatuses[$dsn] = $status;
			if ($this->isServerStatusSuitable($status)) {
				return $result;
			} else {
				$this->logger?->info(sprintf(
					'Connection to %s has become %s and is no longer suitable',
					$connConfig->getDsn(), $status->name)
				);
				return null;
			}
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
				'No suitable database hosts are available for target type %s.',
				$this->config->getDsn()->getTargetServerType()->value)
		);
	}

	private function isServerStatusSuitable(ServerStatus $status): bool {
		if ($status === ServerStatus::UNAVAILABLE) return false;

		$requested = $this->config->getdsn()->getTargetServerType();
		return (
			($requested === TargetServerType::ANY) ||
			($status === ServerStatus::PRIMARY && $requested !== TargetServerType::SECONDARY) ||
			($status === ServerStatus::SECONDARY && $requested !== TargetServerType::PRIMARY)
		);
	}

}
