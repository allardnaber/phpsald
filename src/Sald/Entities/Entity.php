<?php

namespace Sald\Entities;

use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Metadata\MetadataManager;
use Sald\Query\SimpleInsertQuery;
use Sald\Sald;

class Entity {

	private ?Connection $connection = null;
	private array $fields = [];

	/**
	 * Keep track of updated fields.
	 * @var string[]
	 */
	private array $dirty = [];

	public function __construct(?Connection $connection = null, array $fields = []) {
		$this->connection = $connection;
		foreach ($fields as $key => $value) {
			$this->fields[$key] = $value;
		}
	}

	public function __set(string $name, mixed $value): void {
		if (!isset($this->fields[$name]) || $this->fields[$name] !== $value) { // $value instanceof DbExpression ||
			$this->fields[$name] = $value;
			$this->dirty[] = $name;
		}
	}

	public function __get(string $name): mixed {
		return $this->fields[$name] ?? null;
	}

	public function __getAllFields(): array {
		return $this->fields;
	}

	public function save(?Connection $connection = null): bool {
		if ($connection !== null) {
			$this->connection = $connection;
		}
		throw new \RuntimeException('save has not yet been implemented');
	}

	/**
	 * Convenience method for inserting this entity. Uses {@see SimpleInsertQuery}.
	 * @param Connection|null $connection
	 * @return bool
	 */
	public function insert(?Connection $connection = null): bool {
		$this->connection = $connection ?? $this->connection ?? ConnectionManager::get();
		$query = $this->connection->insert($this);
		return $query->execute();
	}

	public function resetDirtyState(): void {
		$this->dirty = [];
	}
}
