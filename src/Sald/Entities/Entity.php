<?php

namespace Sald\Entities;

use ReflectionClass;
use ReflectionProperty;
use Sald\Attributes\JsonExclude;
use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;
use Sald\Metadata\MetadataManager;
use Sald\Query\Expression\Expression;
use Sald\Query\SimpleSelectQuery;

class Entity implements \JsonSerializable {

	/**
	 * The connection which generated this entity. If multiple connections are used, the same connection will be used
	 * to update this entity in the database. It will be null if the entity was created from scratch. Upon inserting
	 * the default connection will be used, or the one that will explicitly be provided when calling {@see self::insert());
	 * @var Connection|null The connection which generated this entity or null if it was created from scratch.
	 */
	private ?Connection $__int_connection;

	/**
	 * All data fields, that may have varying types.
	 * @var array The values, indexed by column name.
	 */
	private array $__int_fields = [];

	/**
	 * Keep track of updated fields.
	 * @var string[] Names of the fields that were changed.
	 */
	private array $__int_dirty = [];

	/**
	 * Cached empty instances, to efficiently create new objects.
	 * @var Entity[]
	 */
	private static array $newInstanceTemplates = [];

	/**
	 * Indexed by classname and field name, keys indicate which fields to include in JSON serialization.
	 * i.e. $jsonSerializableFields[Entity][exampleField] = 1 indicates 'exampleField' should be included.
	 * @var int[][]
	 */
	private static array $jsonSerializableFields = [];

	public function __construct(?Connection $connection = null, array $fields = []) {
		self::collectJsonSerializableFields();

		$reflection = new ReflectionClass(static::class);
		foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
			unset($this->{$prop->getName()});
		}
		$this->__int_connection = $connection;
		$this->setFieldValues($fields);
	}

	public static function newInstance(?Connection $connection = null, array $fields = []): static {
		if (!isset(self::$newInstanceTemplates[static::class])) {
			self::$newInstanceTemplates[static::class] = new static();
		}
		$instance = clone self::$newInstanceTemplates[static::class];
		$instance->__int_connection = $connection;
		$instance->setFieldValues($fields);
		return $instance;
	}

	public static function byId(mixed $id): static {
		return self::select()->whereId($id)->fetchSingle();
	}

	private function setFieldValues(array $fields): void {
		$columns = MetadataManager::getTable(static::class)?->getColumns() ?? [];
		foreach ($columns as $column) {
			if (isset($fields[$column->getDbObjectName()])) {
				$this->__int_fields[$column->getRealObjectName()] = $fields[$column->getDbObjectName()];
				//unset ($fields[$column->getColumnName()]);
			}
		}

		// @todo remaining fields?
		/*foreach ($fields as $key => $value) {
			$this->fields[$key] = $value;
		}*/
	}

	public function __set(string $name, mixed $value): void {
		if (MetadataManager::getTable(static::class)->getColumn($name)?->isEditable() === false) {
			throw new \RuntimeException(
				sprintf('Property %s of %s is not editable.', $name, static::class));
		}
		if (!isset($this->__int_fields[$name]) || $this->__int_fields[$name] !== $value) {
			$this->__int_fields[$name] = $value;
			$this->__int_dirty[] = $name;
			//@todo : editable id fields: old value is required for where clause.
		}
	}

	public function expression(string $name, Expression $expression): void {
		$this->$name = $expression;
	}

	public function getExpression(string $name): ?Expression {
		$value = $this->__int_fields[$name] ?? null;
		return $value instanceof Expression ? $value : null;
	}

	public function __get(string $name): mixed {
		$value = $this->__int_fields[$name] ?? null;
		return $value instanceof Expression ? 'expr:{' . $value->getSQL() . '}' : $value;
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
		$query = $this->__int_connection->updateEntity($this);
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
		$query = $this->__int_connection->insertEntity($this);
		$metadata = MetadataManager::getTable(static::class);
		if (($result = $query->execute()) === true) {
			foreach ($metadata->getIdColumns() as $idColumn) {
				if ($metadata->getColumn($idColumn)->isAutoIncrement()) {
					$this->__int_fields[$idColumn] = $this->__int_connection->lastInsertId();
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
		$query = $this->__int_connection->deleteEntity($this);
		$metadata = MetadataManager::getTable(static::class);
		if (($result = $query->execute()) === true) {
			foreach ($metadata->getIdColumns() as $idColumn) {
				if ($metadata->getColumn($idColumn)->isAutoIncrement()) {
					unset($this->__int_fields[$idColumn]);
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
		return $this->__int_dirty;
	}

	private function resetDirtyState(): void {
		$this->__int_dirty = [];
	}

	private function registerConnectionIfRequired(?Configuration $config = null): void {
		if ($config !== null) {
			$this->__int_connection = ConnectionManager::get($config);
		} elseif ($this->__int_connection === null) {
			$this->__int_connection = ConnectionManager::get();
		}
	}

	public function jsonSerialize(): array {
		return array_filter(
			$this->__int_fields,
			fn(string $fieldName) => isset(self::$jsonSerializableFields[static::class][$fieldName]),
			ARRAY_FILTER_USE_KEY
		);
	}

	private static function collectJsonSerializableFields(): void {
		if (isset(self::$jsonSerializableFields[static::class])) return;

		$reflection = new ReflectionClass(static::class);

		$fieldNames = array_map(
			fn(ReflectionProperty $property) => $property->getName(),
			array_filter(
				$reflection->getProperties(ReflectionProperty::IS_PUBLIC),
				fn(ReflectionProperty $property) => empty($property->getAttributes(JsonExclude::class))
			)
		);

		// use field names as indices for efficient lookup
		self::$jsonSerializableFields[static::class] = array_flip($fieldNames);
	}

}
