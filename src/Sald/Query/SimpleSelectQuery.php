<?php

namespace Sald\Query;

use Sald\Entities\Entity;

/**
 * @template T extends Entity
 */
class SimpleSelectQuery extends AbstractQuery {

	private ?string $alias = null;
	private array $selectFields = [];

	private bool $distinct = false;
	private array $distinctFields = [];
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

	public function getAlias(): ?string {
		return $this->alias;
	}
	
	public function distinct(array|string $fieldOrFields = []): self {
		$this->distinct = true;
		if (is_array($fieldOrFields)) {
			$this->distinctFields = array_merge($this->distinctFields, $fieldOrFields);
		} else {
			$this->distinctFields[] = $fieldOrFields;
		}
		$this->setDirty();
		return $this;
	}
	
	public function fields(array|string $fieldOrFields): self {
		if (is_array($fieldOrFields)) {
			$this->selectFields = array_merge($this->selectFields, $fieldOrFields);
		} else {
			$this->selectFields[] = $fieldOrFields;
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
		return sprintf('%s%s %s', $orderBy, $caseSensitive ?  '' : '', $direction); // @TODO Case sensitive
	}

	private function getDistinctClause(): string {
		$result = '';
		if ($this->distinct) {
			$result .= 'DISTINCT';
			if (!empty($this->distinctFields)) {
				$result .= sprintf(' ON (%s)', join(', ', $this->distinctFields));
			}
		}
		return $result;
	}
	
	protected function buildQuery(): string {
		return join(' ', [
			'SELECT',
			$this->getDistinctClause(),
			empty($this->selectFields) ? '*' : join(', ', $this->selectFields),
			'FROM',
			$this->from,
			$this->alias ?? '',
			join(' ', $this->join),
			$this->getWhereClause(),
			empty($this->groupBy) ? '' : 'GROUP BY ' . join(', ', $this->groupBy),
			empty($this->orderBy) ? '' : 'ORDER BY ' . join(', ', $this->orderBy),
			$this->limit === -1   ? '' : 'LIMIT ' . $this->limit,
			$this->offset === -1  ? '' : 'OFFSET ' . $this->offset
		]);
	}

	/**
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                               objects, 'false' none and with an array only the objects linked to the included
	 *                               property names will be fetched.
	 * @return T[]
	 */
	public function fetchAll(array|bool $deepFetch = true): array {
		$stmt = $this->executeAndGetStatement();
		return $this->connection->fetchAll($stmt, $this->classname, $deepFetch);
	}

	/**
	 * Fetches a single record, accepts precisely one record to be returned and will throw an exception otherwise.
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                               objects, 'false' none and with an array only the objects linked to the included
	 *                               property names will be fetched.
	 * @return T
	 */
	public function fetchSingle(array|bool $deepFetch = true): Entity {
		$stmt = $this->executeAndGetStatement();
		return $this->connection->fetchSingle($stmt, $this->classname, $deepFetch);
	}

	/**
	 * Similar to fetchSingle, but returns null if no records are available and the first record if the query returns
	 * multiple records.
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                               objects, 'false' none and with an array only the objects linked to the included
	 *                               property names will be fetched.
	 * @return T|null Null if the query did not return any records, the first instance of Entity otherwise.
	 */
	public function fetchFirst(array|bool $deepFetch = true): ?Entity {
		$stmt = $this->executeAndGetStatement();
		return $this->connection->fetchFirst($stmt, $this->classname, $deepFetch);
	}

	private function executeAndGetStatement(): \PDOStatement {
		$stmt = $this->connection->prepare($this->getSQL());
		$this->bindValues($stmt);
		if ($this->connection->execute($stmt)) {
			return $stmt;
		} else {
			// @TODO error handling up next
			throw new \RuntimeException('An error occurred');
		}
	}

}
