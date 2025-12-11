<?php

namespace Sald\Entities\Mapper;

use PDO;
use Sald\Connection\Connection;
use Sald\Metadata\TableMetadata;
use Sald\Query\QueryParameter;

abstract class InputMapper {

	private const INPUT_MAPPERS = [
		// psql specific: array to string conversion
		'pgsql' => PgsqlInputMapper::class
	];

	public static function get(Connection $connection, TableMetadata $tableMetadata): self {
		$driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		$mapperClass = self::INPUT_MAPPERS[$driver ?? ''] ?? GenericInputMapper::class;
		return new $mapperClass( $tableMetadata);
	}

	private function __construct(private readonly TableMetadata $tableMetadata) {}

	protected function getTableMetadata(): TableMetadata {
		return $this->tableMetadata;
	}

	/**
	 * Perform data transformations to map entity data to the PDO representation.
	 * Can be used to encode JSON values, stringify arrays, etc
	 * @param QueryParameter $param
	 * @return mixed The converted value
	 */
	abstract public function convertInput(QueryParameter $param): mixed;
}
