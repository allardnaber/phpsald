<?php

namespace Sald\Connection;

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
		return new Connection($this->dsn, $this->username, $this->password, $this->options);
	}

	public function getChecksum(): string {
		return $this->checksum;
	}

}