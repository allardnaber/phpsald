<?php

namespace Sald\Connection\MultiHost;

use PDO;
use Psr\Log\LoggerInterface;
use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\Dsn;
use Sald\Exception\Db\Connection\DbConnectionException;

class MultiHostChooser {

	private const int DEFAULT_HOST_CHECK_CONNECT_TIMEOUT = 2;
	private ?LoggerInterface $logger;

	public function __construct(private readonly Configuration $config) {
		$this->logger = $config->getLogger() ?? null;
	}

	public function getConnection(): Connection {
		return $this->selectHost();
	}

	private function selectHost(): Connection {
		// @todo store hosts status to prevent rechecking on every request
		$basePort = $this->config->getDsn()->getElement(Dsn::ELEM_PORT) ?? Dsn::DEFAULT_PORT;

		$testOptions = $this->config->getOptions();
		$testOptions[PDO::ATTR_TIMEOUT] = $this->config->getHostCheckTimeout() ?? self::DEFAULT_HOST_CHECK_CONNECT_TIMEOUT;

		$hosts = explode(',', $this->config->getDsn()->getElement(Dsn::ELEM_HOST));

		$targetServerType = $this->config->getDsn()->getTargetServerType();
		$suitableConnections = [];

		$this->logger?->debug(sprintf('Selecting 1 (%s) host from %d hosts', $targetServerType->name, count($hosts)));
		foreach ($hosts as $host) {
			$hostParts = explode(':', $host, 2);

			$trialConfig = clone $this->config;
			$trialConfig->setOptions($testOptions);
			$trialConfig->getDsn()->setElement(Dsn::ELEM_HOST, trim($hostParts[0]));
			$trialConfig->getDsn()->setElement(Dsn::ELEM_PORT, trim($hostParts[1] ?? $basePort));

			try {
				$test = new Connection($trialConfig);
			} catch (\PDOException $e) {
				$this->logger?->info(sprintf('Connection to %s failed: %s', $trialConfig->getDsn(), $e->getMessage()));
				continue;
			}
			if ($targetServerType === TargetServerType::ANY) {
				$this->logger?->debug(sprintf('Using %s, as any server type is allowed.', $trialConfig->getDsn()));
				return $test;
			}

			$result = $test->query('show transaction_read_only', PDO::FETCH_ASSOC)->fetch();
			if ($result['transaction_read_only'] === 'off') {
				$this->logger?->debug(sprintf('Connection to %s fully operational (primary).', $trialConfig->getDsn()));
				// writable (= primary)
				if (in_array($targetServerType, [TargetServerType::PRIMARY, TargetServerType::PREFER_PRIMARY])) {
					$this->logger?->info(sprintf('Using primary host %s.', $trialConfig->getDsn()));
					return $test;
				} elseif ($targetServerType === TargetServerType::PREFER_SECONDARY) {
					$this->logger?->debug(sprintf('Keeping primary %s as one of the suitable connections.', $trialConfig->getDsn()));
					$suitableConnections[] = $test;
				}
			} else {
				$this->logger?->info(sprintf('Connection to %s is in readonly mode (secondary).', $trialConfig->getDsn()));
				// secondary
				if (in_array($targetServerType, [TargetServerType::SECONDARY, TargetServerType::PREFER_SECONDARY])) {
					$this->logger?->info(sprintf('Using secondary host %s.', $trialConfig->getDsn()));
					return $test;
				} elseif ($targetServerType === TargetServerType::PREFER_PRIMARY) {
					$this->logger?->debug(sprintf('Keeping secondary %s as one of the suitable connections.', $trialConfig->getDsn()));
					$suitableConnections[] = $test;
				}
			}
		}

		if (!empty($suitableConnections)) {
			$this->logger?->info('Using the first suitable connection that was inspected earlier.');
			return $suitableConnections[0];
		}
		throw new DbConnectionException(sprintf('No suitable database hosts are available for target type %s', $targetServerType->value));
	}

}
