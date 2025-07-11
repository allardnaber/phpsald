<?php /** @noinspection SqlNoDataSourceInspection */

namespace Sald\Query;

use RuntimeException;

class SimpleDeleteQuery extends AbstractMutatingQuery {

	protected function buildQuery(): string {
		if (empty($this->where)) {
			throw new RuntimeException('Attempting to perform an unqualified DELETE. Aborted.');
		}
		/** @noinspection SqlWithoutWhere Where clause is added dynamically */
		return sprintf('DELETE FROM %s %s', $this->from, $this->getWhereClause());
	}

}
