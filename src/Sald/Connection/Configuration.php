<?php

namespace Sald\Connection;

use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;

class Configuration {

	private Dsn $parsedDsn;

	private string $dsn;
	private string $username;
	private string $password;
	private ?array $options;
	private ?string $schema;

	private ?LoggerInterface $logger;

	/*
	 * Timeout in seconds used when trying to verify a host in a multihost setup
	 */
	private int $hostCheckTimeout;

	private static array $requiredConfig = [ 'dsn', 'username', 'password' ];
	private static array $allowedConfig =  [ 'dsn', 'username', 'password', 'options', 'schema', 'logger', 'hostCheckTimeout' ];

	private static array $checksumElements = ['dsn', 'username', 'password', 'options', 'schema'];

	public function __construct(#[SensitiveParameter] array $config) {
		$missing = array_diff(self::$requiredConfig, array_keys($config));
		if (!empty($missing)) {
			throw new RuntimeException(
				sprintf('Missing required config field(s) %s for database configuration', implode(', ', $missing))
			);
		}

		foreach (self::$allowedConfig as $option) {
			if (isset($config[$option])) {
				$this->$option = $config[$option];
			}
		}

		$this->parsedDsn = new Dsn($config['dsn']);
	}

	public function getDsn(): Dsn {
		return $this->parsedDsn;
	}

	public function getRawDsn(): string {
		return $this->dsn;
	}

	public function getUsername(): string {
		return $this->username;
	}

	public function getPassword(): string {
		return $this->password;
	}

	public function getOptions(): array {
		return $this->options ?? [];
	}

	public function getSchema(): ?string {
		return $this->schema ?? null;
	}

	public function getLogger(): ?LoggerInterface {
		return $this->logger ?? null;
	}

	public function getChecksum(): string {
		$v = [];
		foreach (self::$checksumElements as $element) {
			$v[$element] = $this->$element;
		}
		return md5(json_encode($v));
	}

	public function getHostCheckTimeout(): ?int {
		return $this->hostCheckTimeout ?? null;
	}

	public function setUsername(string $username): void {
		$this->username = $username;
	}

	public function setPassword(string $password): void {
		$this->password = $password;
	}

	public function setOptions(array $options): void {
		$this->options = $options;
	}

	public function setSchema(?string $schema): void {
		$this->schema = $schema;
	}

	public function setHostCheckTimeout(int $hostCheckTimeout): void {
		$this->hostCheckTimeout = $hostCheckTimeout;
	}

	public function __clone() {
		$this->parsedDsn = clone $this->parsedDsn;
		$this->options = isset($this->options) ? clone $this->options : [];
	}
}
