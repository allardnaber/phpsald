<?php

namespace Sald\Entities\Mapper;

use PDO;
use PDOStatement;
use Sald\Connection\Connection;
use Sald\Entities\Entity;

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

	public function fetch(string $classname): Entity|bool {
		$result = $this->statement->fetch(PDO::FETCH_ASSOC);
		return $result === false ? false : $this->asEntity($this->convertRecord($result), $classname);
	}

	public function fetchAll(string $classname): array|bool {
		$result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
		if ($result === false) return false;

		return array_map(fn($record) => $this->asEntity($this->convertRecord($record), $classname), $result);
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
