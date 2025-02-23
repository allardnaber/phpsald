<?php

namespace Sald\Entities;

use ReflectionClass;
use ReflectionProperty;
use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Metadata\MetadataManager;
use Sald\Metadata\TableMetadata;
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

	/**
	 * Keep track of updated fields.
	 * @var string[] Names of the fields that were changed.
	 */
	private array $dirty = [];

	/**
	 * Cached empty instance, to efficiently create a new object.
	 * @var Entity[]
	 */
	private static array $newInstanceTemplates = [];


	public function __construct(?Connection $connection = null, array $fields = []) {
		$reflection = new ReflectionClass(static::class);
		foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
			unset($this->{$prop->getName()});
		}
		$this->connection = $connection;
		$this->setFieldValues($fields);
	}

	public static function newInstance(?Connection $connection = null, array $fields = []): static {
		if (!isset(self::$newInstanceTemplates[static::class])) {
			self::$newInstanceTemplates[static::class] = new static();
		}
		$instance = clone self::$newInstanceTemplates[static::class];
		$instance->connection = $connection;
		$instance->setFieldValues($fields);
		return $instance;
	}

	private function setFieldValues(array $fields): void {
		$columns = MetadataManager::getTable(static::class)?->getColumns() ?? [];
		foreach ($columns as $column) {
			if (isset($fields[$column->getColumnName()])) {
				$this->fields[$column->getPropertyName()] = $fields[$column->getColumnName()];
				//unset ($fields[$column->getColumnName()]);
			}
		}

		// @todo remaining fields?
		/*foreach ($fields as $key => $value) {
			$this->fields[$key] = $value;
		}*/
	}

	public function __set(string $name, mixed $value): void {
		if (!MetadataManager::getTable(static::class)->getColumn($name)->isEditable()) {
			throw new \RuntimeException(
				sprintf('Property %s of %s is not editable.', $name, static::class));
		}
		if (!isset($this->fields[$name]) || $this->fields[$name] !== $value) {
			$this->fields[$name] = $value;
			$this->dirty[] = $name;
		}
	}

	public function expression(string $name, Expression $expression): void {
		$this->__set($name, $expression);
	}

	public function getExpression(string $name): ?Expression {
		$value = $this->fields[$name] ?? null;
		return $value instanceof Expression ? $value : null;
	}

	public function __get(string $name): mixed {
		$value = $this->fields[$name] ?? null;
		return $value instanceof Expression ? 'expr:{' . $value->getSQL() . '}' : $value;
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
		$metadata = MetadataManager::getTable(static::class);
		if (($result = $query->execute()) === true) {
			foreach ($metadata->getIdColumns() as $idColumn) {
				if ($metadata->getColumn($idColumn)->isAutoIncrement()) {
					$this->fields[$idColumn] = $this->connection->lastInsertId();
					// @todo how to handle multi column insertions?
				}
			}
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
		$metadata = MetadataManager::getTable(static::class);
		if (($result = $query->execute()) === true) {
			foreach ($metadata->getIdColumns() as $idColumn) {
				if ($metadata->getColumn($idColumn)->isAutoIncrement()) {
					unset($this->fields[$idColumn]);
				}
			}
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
