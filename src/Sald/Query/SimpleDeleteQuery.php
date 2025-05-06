<?php

namespace Sald\Query;

class SimpleDeleteQuery extends AbstractMutatingQuery {

	protected function buildQuery(): string {
		if (empty($this->where)) {
			throw new \RuntimeException('Attempting to perform an unqualified DELETE. Aborted.');
		}
		return sprintf('DELETE FROM %s %s', $this->from, $this->getWhereClause());
	}

}
