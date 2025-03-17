<?php

namespace Sald\Query;

class SimpleDeleteQuery extends AbstractQuery {

	public function execute(): bool {
		$stmt = $this->connection->prepare($this->getSQL());
		$this->bindValues($stmt);

		return $this->connection->execute($stmt);
	}

	protected function buildQuery(): string {
		if (empty($this->where)) {
			throw new \RuntimeException('Attempting to perform an unqualified DELETE. Aborted.');
		}
		return sprintf('DELETE FROM %s %s', $this->from, $this->getWhereClause());
	}

}