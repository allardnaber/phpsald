<?php

namespace Sald\Query;

class SimpleSelectQuery extends AbstractQuery {

	private ?string $alias = null;
	private array $selectFields = [];
	private array $join = [];
	private array $orderBy = [];
	private array $groupBy = [];

	public function alias(string $alias): self {
		$this->setDirty();
		$this->alias = $alias;
		return $this;
	}
	
	public function fields(array|string $field): self {
		$this->setDirty();
		if (is_array($field)) {
			$this->selectFields = array_merge($this->selectFields, $field);
		} else {
			$this->selectFields[] = $field;
		}
		return $this;
	}
	
	public function join(string $table, string $condition, string $direction = 'INNER'): self {
		$this->setDirty();
		$this->join[] = $this->buildJoinClause($direction, $table, $condition);
		return $this;
	}

	public function orderBy(string $orderBy, string $direction = 'ASC', bool $caseSensitive = false): self {
		$this->setDirty();
		$this->orderBy[] = $this->buildOrderByClause($orderBy, $direction, $caseSensitive);
		return $this;
	}

	public function groupBy(string $groupBy): self {
		$this->setDirty();
		$this->groupBy[] = $groupBy;
		return $this;
	}

	public function getTableName(): string {
		return $this->alias ?? $this->from;
	}

	private function buildJoinClause(string $direction, string $table, string $condition): string {
		return sprintf('%s JOIN %s ON %s', $direction, $table, $condition);
	}

	private function buildOrderByClause(string $orderBy, string $direction, bool $caseSensitive): string {
		return sprintf('%s%s %s', $orderBy, $caseSensitive ?  '' : ' COLLATE NOCASE', $direction);
	}
	
	protected function buildQuery(): string {
		return sprintf(
			'SELECT %s FROM %s %s %s %s %s %s',
			empty($this->selectFields) ? '*' : join(', ', $this->selectFields),
			$this->from,
			$this->alias ?? '',
			join(' ', $this->join),
			empty($this->where)   ? '' : 'WHERE ' . join(' AND ', $this->where),
			empty($this->groupBy) ? '' : 'GROUP BY ' . join(', ', $this->groupBy),
			empty($this->orderBy) ? '' : 'ORDER BY ' . join(', ', $this->orderBy)
		);
	}

	public function fetchAll(): array {
		$stmt = $this->connection->prepare($this->getSQL());
		// $stmt->bindValue()...
		if ($stmt->execute()) {
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			return $this->connection->fetchAllAsObjects($result, $this->classname);
		}

	}
}