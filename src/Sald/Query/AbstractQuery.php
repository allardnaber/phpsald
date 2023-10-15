<?php

namespace Sald\Query;

use Sald\Connection\Connection;
use Sald\Metadata\TableMetadata;

abstract class AbstractQuery {
	
	protected string $from;
	private string $query;
	private bool $dirty = true;
	protected array $where = [];

	protected TableMetadata $tableMetadata;

	protected string $classname;
	protected Connection $connection;
	
	public function __construct(Connection $connection, TableMetadata $metadata, string $className) {
		$this->connection = $connection;
		$this->tableMetadata = $metadata;
		$this->classname = $className;
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

	
	protected function setDirty(): void {
		$this->dirty = true;
	}
}
	