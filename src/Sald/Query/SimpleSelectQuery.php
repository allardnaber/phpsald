<?php

namespace Sald\Query;

class SimpleSelectQuery extends AbstractQuery {

	private ?string $alias = null;
	private array $selectFields = [];
	private array $join = [];
	private array $orderBy = [];
	private array $groupBy = [];

	private int $offset = -1;
	private int $limit = -1;

	public function alias(string $alias): self {
		$this->alias = $alias;
		$this->setDirty();
		return $this;
	}
	
	public function fields(array|string $field): self {
		if (is_array($field)) {
			$this->selectFields = array_merge($this->selectFields, $field);
		} else {
			$this->selectFields[] = $field;
		}
		$this->setDirty();
		return $this;
	}
	
	public function join(string $table, string $condition, string $direction = 'INNER'): self {
		$this->join[] = $this->buildJoinClause($direction, $table, $condition);
		$this->setDirty();
		return $this;
	}

	public function orderBy(string $orderBy, string $direction = 'ASC', bool $caseSensitive = false): self {
		$this->orderBy[] = $this->buildOrderByClause($orderBy, $direction, $caseSensitive);
		$this->setDirty();
		return $this;
	}

	public function groupBy(string $groupBy): self {
		$this->groupBy[] = $groupBy;
		$this->setDirty();
		return $this;
	}

	public function limit(int $limit = -1, int $offset = -1): self {
		$this->setDirty();
		$this->offset = $offset;
		$this->limit = $limit;
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
			'SELECT %s FROM %s %s %s %s %s %s %s %s',
			empty($this->selectFields) ? '*' : join(', ', $this->selectFields),
			$this->from,
			$this->alias ?? '',
			join(' ', $this->join),
			empty($this->where)   ? '' : 'WHERE ' . join(' AND ', $this->where),
			empty($this->groupBy) ? '' : 'GROUP BY ' . join(', ', $this->groupBy),
			empty($this->orderBy) ? '' : 'ORDER BY ' . join(', ', $this->orderBy),
			$this->limit === -1 ? '' : 'LIMIT ' . $this->limit,
			$this->offset === -1 ? '' : 'OFFSET ' . $this->offset,
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