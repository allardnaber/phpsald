<?php

namespace Sald\Connection;

use Sald\Connection\MultiHost\TargetServerType;
use Sald\Exception\SaldPDOException;
use Stringable;

class Dsn implements Stringable {

	public const int DEFAULT_PORT = 5432;
	public const string PARAM_SERVER_TYPE = 'targetServerType';
	public const string ELEM_HOST = 'host';
	public const string ELEM_PORT = 'port';

	private string $driver;
	private array $dsnParts;

	private bool $isMultiHost;
	private ?TargetServerType $targetServerType = null;

	public function __construct(string $dsn) {
		list ($this->driver, $dsnDetails) = explode(':', $dsn, 2);
		if (!isset($dsnDetails)) {
			throw new SaldPDOException(sprintf('Database driver is missing from dsn %s.', $dsn));
		}
		foreach(explode(';', $dsnDetails) as $dsnPart) {
			list ($k, $v) = explode('=', $dsnPart, 2);
			$this->dsnParts[$k] = $v;
		}

		$this->isMultiHost = $this->calculateMultiHost();
	}

	public function getDriver(): string {
		return $this->driver;
	}

	public function getElement(string $name): mixed {
		return $this->dsnParts[$name] ?? null;
	}

	public function hasElement(string $name): bool {
		return isset($this->dsnParts[$name]) && $this->dsnParts[$name] !== null;
	}

	public function removeElement(string $name): void {
		unset($this->dsnParts[$name]);
	}

	public function setElement(string $name, mixed $value): void {
		$this->dsnParts[$name] = $value;
	}

	public function getTargetServerType(): TargetServerType {
		return $this->targetServerType ?? TargetServerType::ANY;
	}

	public function isMultiHost(): bool {
		return $this->isMultiHost;
	}

	private function calculateMultiHost(): bool {
		if ($this->hasElement(self::PARAM_SERVER_TYPE)) {
			$this->targetServerType = TargetServerType::from($this->getElement(self::PARAM_SERVER_TYPE));
			$this->removeElement(self::PARAM_SERVER_TYPE);
			return true;
		}

		$host = $this->getElement(self::ELEM_HOST);
		if (is_string($host) && str_contains($host, ',')) return true;

		return false;
	}

	public function __toString(): string {
		return sprintf('%s:%s',
			$this->driver,
			join(';', array_map(
				fn ($v, $k) => $k . '=' . $v,
				$this->dsnParts,
				array_keys($this->dsnParts)
			)));
	}
}