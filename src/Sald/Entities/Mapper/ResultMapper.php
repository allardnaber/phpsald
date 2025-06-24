<?php

namespace Sald\Entities\Mapper;

use PDO;
use PDOStatement;
use Sald\Connection\Connection;
use Sald\Entities\Entity;
use Sald\Metadata\ColumnMetadata;
use Sald\Metadata\MetadataManager;
use Sald\Query\Expression\Operator;
use Sald\Sald;
use Sald\Util;

abstract class ResultMapper {

	private const RESULT_MAPPERS = [
		'pgsql' => PgsqlResultMapper::class
	];

	public static function get(Connection $connection, PDOStatement $statement): self {
		$driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		$mapperClass = self::RESULT_MAPPERS[$driver ?? ''] ?? GenericResultMapper::class;
		return new $mapperClass($connection, $statement);
	}

	/**
	 * @var ResultColumnMetadata[]
	 */
	private array $metadata = [];

	private function __construct(private readonly Connection $connection, private readonly PDOStatement $statement) {
		$this->fetchMetadata();
	}

	private function fetchMetadata(): void {
		for ($c = 0; $c < $this->statement->columnCount(); $c++) {
			$metadata = $this->statement->getColumnMeta($c);
			if ($metadata !== false) {
				$this->metadata[] = new ResultColumnMetadata($metadata);
			}
		}
	}

	public function fetch(string $classname, array|bool $deepFetch = true): Entity|bool {
		$result = $this->statement->fetch(PDO::FETCH_ASSOC);
		if ($result === false) return false;

		$record = $this->asEntity($this->convertRecord($result), $classname);
		$this->deepFetch($classname, [ $record ], $deepFetch);
		return $record;
	}

	public function fetchAll(string $classname, array|bool $deepFetch = true): array|bool {
		$result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
		if ($result === false) return false;

		$records = array_map(fn($record) => $this->asEntity($this->convertRecord($record), $classname), $result);
		$this->deepFetch($classname, $records, $deepFetch);

		return $records;
	}

	/**
	 * @param string $classname
	 * @param Entity[] $records
	 * @param string[]|bool $deepFetch
	 * @return void
	 */
	private function deepFetch(string $classname, array $records, array|bool $deepFetch = true): void {
		if ($deepFetch === false) return;

		foreach (MetadataManager::getTable($classname)->getColumns() as $column) {
			if (
				$column->getRelation() !== null &&
				($deepFetch === true || in_array($column->getRealObjectName(), $deepFetch))
			) {
				$this->deepFetchProperty($records, $column);
			}
		}
	}

	/**
	 * @param array $records
	 * @param ColumnMetadata $column
	 * @return void
	 */
	private function deepFetchProperty(array &$records, ColumnMetadata $column): void {
		$relation = $column->getRelation();

		$referencedIdColumn = $relation->getReferencedBy();
		$referencedIds = Util::getUniqueValues($records, $referencedIdColumn);

		if (empty($referencedIds)) {
			foreach ($records as $record) {
				$record->__set_non_dirty($column->getRealObjectName(), []);
			}
			return;
		}

		$referencedColumnName = MetadataManager::getTable($relation->getClassname())
			->getColumn($relation->getReferences())
			->getDbObjectName();

		$query = Sald::select($relation->getClassname())
			->where($referencedColumnName, $referencedIds, Operator::ANY);
		if ($relation->getCondition() !== null) {
			$query->addCondition($relation->getCondition());
		}
		if ($relation->getTableName() !== null) {
			$query->overrideTableName($relation->getTableName());
		}

		$referencedRecords = $query->fetchAll();
		Util::indexByField($referencedRecords, $relation->getReferences());

		foreach ($records as $record) {
			$record->__set_non_dirty($column->getRealObjectName(), array_values($referencedRecords[$record->$referencedIdColumn] ?? []));
		}

	}

	private function asEntity(array $record, string $classname): Entity {
		return $classname::newInstance($this->connection, $record);
	}

	/**
	 * @return ResultColumnMetadata[]
	 */
	protected function getMetadata(): array {
		return $this->metadata;
	}

	/**
	 * Perform driver specific transformations to map the PDO representation to data that matches the entity.
	 * Can be used to decode arrays, date/time values, etc.
	 * @param array $record
	 * @return array
	 */
	abstract protected function convertRecord(array $record): array;
}
