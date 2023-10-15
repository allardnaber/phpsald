<?php

namespace Sald\Entities;

use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Metadata\MetadataManager;
use Sald\Query\SimpleInsertQuery;

class Entity {

	private ?Connection $connection = null;
	private array $fields = [];

	private string $idColumn;

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
		$this->idColumn = MetadataManager::getTable(static::class)->getIdColumnName();
	}

	public function __set(string $name, mixed $value): void {
		if ($name === $this->idColumn) {
			throw new \RuntimeException(
				sprintf('Property %s is the id field of %s, and thus readonly.', $name, static::class));
		}
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

	public function update(?Connection $connection = null): bool {
		$this->connection = $connection ?? $this->connection ?? ConnectionManager::get();
		$query = $this->connection->updateEntity($this);
		if (($result = $query->execute()) === true) {
			$this->resetDirtyState();
		}
		return $result;
	}

	/**
	 * Convenience method for inserting this entity. Uses {@see SimpleInsertQuery}.
	 * @param Connection|null $connection
	 * @return bool
	 */
	public function insert(?Connection $connection = null): bool {
		$this->connection = $connection ?? $this->connection ?? ConnectionManager::get();
		$query = $this->connection->insertEntity($this);
		if (($result = $query->execute()) === true) {
			$idColumn = MetadataManager::getTable(static::class)->getIdColumnName();
			$this->fields[$idColumn] = $this->connection->lastInsertId();
			$this->resetDirtyState();
		}
		return $result;
	}

	public function delete(?Connection $connection = null): bool {
		$this->connection = $connection ?? $this->connection ?? ConnectionManager::get();
		$query = $this->connection->deleteEntity($this);
		if (($result = $query->execute()) === true) {
			$idColumn = MetadataManager::getTable(static::class)->getIdColumnName();
			unset($this->fields[$idColumn]);
			$this->resetDirtyState();
		}
		return $result;
	}

	/**
	 * @return string[]
	 */
	public function getDirtyFields(): array {
		return $this->dirty;
	}

	private function resetDirtyState(): void {
		$this->dirty = [];
	}
}
