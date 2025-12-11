<?php /** @noinspection SqlNoDataSourceInspection */

namespace Sald\Query;

class SimpleInsertQuery extends AbstractMutatingQuery {

	protected function buildQuery(): string {
		return sprintf ('INSERT INTO %s (%s) VALUES (%s)',
			$this->from,
			join(', ', array_map(fn(QueryParameter $p) => $p->getColumnName(), $this->getMutations())),
			join(', ', array_map(fn(QueryParameter $p) => $p->getPlaceholderName(), $this->getMutations()))
		);
	}

}
