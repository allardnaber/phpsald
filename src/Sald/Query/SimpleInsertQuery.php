<?php /** @noinspection SqlNoDataSourceInspection */

namespace Sald\Query;

use Sald\Query\Expression\Expression;

class SimpleInsertQuery extends AbstractMutatingQuery {

	protected function buildQuery(): string {
		return sprintf ('INSERT INTO %s (%s) VALUES (%s)',
			$this->from,
			join(', ', array_map(fn(QueryParameter $p) => $p->getColumnName(), $this->getMutations())),
			join(', ', array_map(fn(QueryParameter $p) => $this->getMutationValueSql($p), $this->getMutations()))
		);
	}

	protected function getMutationValueSql(QueryParameter $mutation): string {
		if ($mutation->getValue() instanceof Expression) {
			return $mutation->getValue()->getExpression();
		} else {
			return $mutation->getPlaceholderName();
		}
	}

}
