<?php

namespace Sald\Query;

use Sald\Query\Expression\Expression;

class SimpleUpdateQuery extends AbstractMutatingQuery {

	protected function buildQuery(): string {
		$updateFields = array_map(fn(QueryParameter $m) => $this->getMutationSql($m), $this->getMutations());

		return join(' ', [
			'UPDATE',
			$this->from,
			'SET',
			join(', ', $updateFields),
			$this->getWhereClause()
		]);
	}

	protected function getMutationSql(QueryParameter $mutation): string {
		if ($mutation->getValue() instanceof Expression) {
			return sprintf('%s=(%s)', $mutation->getColumnName(), $mutation->getValue()->getExpression());
		} else {
			return sprintf('%s=%s', $mutation->getColumnName(), $mutation->getPlaceholderName());
		}
	}

	public function execute(): bool {
		return !$this->hasMutations() || parent::execute();
	}
}
