<?php

namespace Sald\Entities;

use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Metadata\MetadataManager;
use Sald\Query\Expression\Expression;
use Sald\Query\SimpleSelectQuery;

class Entity {

	/**
	 * The connection which generated this entity. If multiple connections are used, the same connection will be used
	 * to update this entity in the database. It will be null if the entity was created from scratch. Upon inserting
	 * the default connection will be used, or the one that will explicitly be provided when calling {@see self::insert());
	 * @var Connection|null The connection which generated this entity or null if it was created from scratch.
	 */
	private ?Connection $connection;

	/**
	 * All data fields, that may have varying types.
	 * @var array The values, indexed by column name.
	 */
	private array $fields = [];



	private string $idColumn;

	/**
	 * Keep track of updated fields.
	 * @var string[] Names of the fields that were changed.
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
		if (!isset($this->fields[$name]) || $this->fields[$name] !== $value) {
			$this->fields[$name] = $value;
			$this->dirty[] = $name;
		}
	}

	public function expression(string $name, Expression $expression): void {
		$this->fields[$name] = $expression;
		$this->dirty[] = $name;
	}

	public function getExpression(string $name): ?Expression {
		$value = $this->fields[$name] ?? null;
		return $value instanceof Expression ? $value : null;
	}

	public function __get(string $name): mixed {
		$value = $this->fields[$name] ?? null;
		return $value instanceof Expression ? 'expr:{' . $value . '}' : $value;
	}

	public function __getAllFields(): array {
		return $this->fields;
	}

	/**
	 * Creates a select query to retrieve one or more instances if this entity.
	 * @param Configuration|null $config Configuration to get a specific connection, use default connection if omitted.
	 * @return SimpleSelectQuery The base query to which criteria or other SQL elements can be added.
	 */
	public static function select(?Configuration $config = null): SimpleSelectQuery {
		return ConnectionManager::get($config)->select(static::class);
	}

	/**
	 * Updates the current, already existing, entity in the database.
	 * @param Configuration|null $config Configuration to get a specific connection, use default connection if omitted.
	 * @return bool True if the update statement was executed successfully.
	 */
	public function update(?Configuration $config = null): bool {
		$this->registerConnectionIfRequired($config);
		$query = $this->connection->updateEntity($this);
		if (($result = $query->execute()) === true) {
			$this->resetDirtyState();
		}
		return $result;
	}

	/**
	 * Inserts the current new entity into the database and sets the id column to the newly generated id.
	 * @param Configuration|null $config Configuration to get a specific connection, use default connection if omitted.
	 * @return bool True if the insert statement was executed successfully.
	 */
	public function insert(?Configuration $config = null): bool {
		$this->registerConnectionIfRequired($config);
		$query = $this->connection->insertEntity($this);
		if (($result = $query->execute()) === true) {
			$idColumn = MetadataManager::getTable(static::class)->getIdColumnName();
			$this->fields[$idColumn] = $this->connection->lastInsertId();
			$this->resetDirtyState();
		}
		return $result;
	}

	/**
	 * Deletes the current entity from the database.
	 * @param Configuration|null $config Configuration to get a specific connection, use default connection if omitted.
	 * @return bool True if the delete statement was executed successfully.
	 */
	public function delete(?Configuration $config = null): bool {
		$this->registerConnectionIfRequired($config);
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

	private function registerConnectionIfRequired(?Configuration $config = null): void {
		if ($config !== null) {
			$this->connection = ConnectionManager::get($config);
		} elseif ($this->connection === null) {
			$this->connection = ConnectionManager::get();
		}
	}
}
