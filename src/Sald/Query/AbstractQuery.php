<?php

namespace Sald\Query;

use PDOStatement;
use Sald\Connection\Connection;
use Sald\Metadata\TableMetadata;
use Sald\Query\Expression\Comparator;
use Sald\Query\Expression\Condition;
use Sald\Query\Expression\Expression;

abstract class AbstractQuery {
	
	protected string $from;
	private string $query;
	private bool $dirty = true;
	/**
	 * @var Condition[]
	 */
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
	
	public function whereLiteral($where): self {
		$this->setDirty();
		$this->where[] = $where;
		return $this;
	}

	public function where(string $field, mixed $value, Comparator $comparator = Comparator::EQ): self {
		$this->setDirty();

		if ($value instanceof Expression) {
			$insertVal = $value->getSQL();
		} else {
			$insertVal = $value === null ? null : ':__where_' . $field;
			$this->parameter('__where_' . $field, $value);
		}

		$this->where[] = new Condition($field, $comparator, $insertVal);
		return $this;
	}

	public function whereId(mixed $value, Comparator $comparator = Comparator::EQ): self {
		$idField = $this->tableMetadata->getIdColumnName();
		return $this->where($idField, $value, $comparator);
	}

	protected function getWhereClause(): string {
		if (empty($this->where)) {
			return '';
		}
		return 'WHERE ' . join(' AND ', array_map(fn($e) => $e->getSQL(), $this->where));
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
			if (is_bool($value)) {
				$statement->bindValue($key, $value, \PDO::PARAM_BOOL);
			} else if (!($value instanceof Expression)) {
				$statement->bindValue($key, $value);
			}
		}
	}
}
	