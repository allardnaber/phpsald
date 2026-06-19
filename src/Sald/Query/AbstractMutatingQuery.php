<?php

namespace Sald\Query;

use Sald\Exception\SaldPDOException;

abstract class AbstractMutatingQuery extends AbstractQuery {

	/**
	 * @var QueryParameter[]
	 */
	private array $mutations = [];

	private \PDOStatement $stmt;

	public function set(string $key, mixed $value): self {
		$this->mutations[] = new QueryParameter($key, $value, 'write');
		return $this;
	}

	public function execute(): bool {
		$this->stmt = $this->connection->prepare($this->getSQL());
		$this->bindValues($this->stmt);
		$this->bindValuesFromQueryParams($this->stmt, $this->mutations);

		return $this->connection->execute($this->stmt);
	}

	public function getRowCount(): int {
		if (!isset($this->stmt)) {
			throw new SaldPDOException('Row count is only available after executing query');
		}
		return $this->stmt->rowCount();
	}

	/**
	 * @return QueryParameter[]
	 */
	protected function getMutations(): array {
		return $this->mutations;
	}

	protected function hasMutations(): bool {
		return !empty($this->mutations);
	}
}
