<?php

namespace Sald\Query;

use PDOStatement;
use Sald\Connection\Connection;
use Sald\Metadata\TableMetadata;
use Sald\Query\Expression\Expression;

abstract class AbstractQuery {
	
	protected string $from;
	private string $query;
	private bool $dirty = true;
	protected array $where = [];

	protected array $parameters = [];

	protected TableMetadata $tableMetadata;

	protected string $classname;
	protected Connection $connection;
	
	public function __construct(Connection $connection, TableMetadata $metadata) {
		$this->connection = $connection;
		$this->tableMetadata = $metadata;
		$this->classname = $metadata->getClassname();
		$this->from = $metadata->getTableName();
	}

	abstract protected function buildQuery(): string;
	
	public function getSQL(): string {
		if ($this->dirty) {
			$this->query = $this->buildQuery();
			$this->dirty = false;
		}
		return $this->query;
	}
	
	public function where($where): self {
		$this->setDirty();
		$this->where[] = $where;
		return $this;
	}

	public function parameter(string $key, mixed $value): self {
		$this->parameters[$key] = $value;
		return $this;
	}

	
	protected function setDirty(): void {
		$this->dirty = true;
	}


	protected function bindValues(PDOStatement $statement): void {
		foreach ($this->parameters as $key => $value) {
			if (!($value instanceof Expression)) {
				$statement->bindValue($key, $value, \PDO::PARAM_STR);
			}
		}
	}
}
	