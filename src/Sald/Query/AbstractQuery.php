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

	public function where(string $field, Comparator $comparator, mixed $value): self {
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

	protected function getWhereClause(): string {
		if (empty($this->where)) {
			return '';
		}
		$result = 'WHERE ' . join(' AND ', array_map(fn($e) => $e->getSQL(), $this->where));
		echo $result;
		return $result;
		//die();

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
				print_r($key);
				print_r($value);
				$statement->bindValue($key, $value);//, \PDO::PARAM_STR);
			}
		}
	}
}
	