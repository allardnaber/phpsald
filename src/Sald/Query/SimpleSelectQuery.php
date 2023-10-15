<?php

namespace Sald\Query;

use Sald\Entities\Entity;
use Sald\Exception\RecordNotFoundException;

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
		$result = [];
		$stmt = $this->connection->prepare($this->getSQL());
		// $stmt->bindValue()...
		if ($stmt->execute()) {
			foreach ($stmt->fetchAll() as $record) {
				$result[] = $this->asEntity($record);
			}
		}
		return $result;
	}

	/**
	 * Fetches a single record, accepts precisely one record to be returned and will throw an exception otherwise.
	 * @return Entity
	 */
	public function fetchSingle(): Entity {
		return $this->fetchOneRecord(true);
	}

	/**
	 * Similar to fetchSingle, but returns null if no records are available and the first record if the query returns
	 * multiple records.
	 * @return Entity|null Null if the query did not return any records, the first instance of Entity otherwise.
	 */
	public function fetchFirst(): ?Entity {
		return $this->fetchOneRecord(false);
	}

	private function fetchOneRecord(bool $strict): ?Entity {
		$stmt = $this->connection->prepare($this->getSQL());
		// $stmt->bindValue()...
		if ($stmt->execute()) {
			$result = $stmt->fetch();
			if ($result === false) {
				if ($strict) {
					throw new RecordNotFoundException(
						sprintf('Required record for %s was not found.', $this->tableMetadata->getClassname()));
				} else {
					return null;
				}
			} else {
				return $this->asEntity($result);
			}
		} else {
			throw new \RuntimeException('An error occurred');
		}
	}

	private function asEntity(array $record): Entity {
		$cln = $this->classname;
		return new $cln($this->connection, $record);
	}
}
