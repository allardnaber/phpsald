<?php

namespace Sald\Query;

use Sald\Connection\Connection;
use Sald\Entities\Entity;
use Sald\Metadata\MetadataManager;
use Sald\Metadata\TableMetadata;

class SimpleInsertQuery extends AbstractQuery {

	private Entity $entity;

	private array $keys;

	public function __construct(Connection $connection, TableMetadata $metadata, Entity $entity) {
		parent::__construct($connection, $metadata);
		$this->entity = $entity;
	}

	protected function buildQuery(): string {
		$this->keys = array_keys($this->entity->__getAllFields());
		$idColumnIdx = array_search($this->tableMetadata->getIdColumnName(), $this->keys);
		array_splice($this->keys, $idColumnIdx, 1);
		$keysPrefixed = array_map(fn($e) => ':' . $e, $this->keys);

		return sprintf ('INSERT INTO %s (%s) VALUES (%s)',
			$this->from,
			join(', ', $this->keys),
			join(', ', $keysPrefixed)
		);
	}

	public function execute(): bool {
		$idColumnName = $this->tableMetadata->getIdColumnName();
		$stmt = $this->connection->prepare($this->getSQL());
		$values = $this->entity->__getAllFields();
		foreach ($this->keys as $key) {
			$stmt->bindValue($key, $values[$key]);
		}

		if (($result = $stmt->execute()) === true) {
			$this->entity->$idColumnName = $this->connection->lastInsertId();
			$this->entity->resetDirtyState();
		}

		return $result;
	}
}