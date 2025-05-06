<?php

namespace Sald\Query;

use Sald\Query\Expression\Expression;

class SimpleUpdateQuery extends AbstractMutatingQuery {

	protected function buildQuery(): string {
		$updateFields = [];

		foreach ($this->getMutations() as $mutation) {
			if ($mutation->getValue() instanceof Expression) {
				$updateFields[] = sprintf('%s=(%s)', $mutation->getColumnName(), $mutation->getValue()->getExpression());
			} else {
				$updateFields[] = sprintf('%s=%s', $mutation->getColumnName(), $mutation->getPlaceholderName());
			}
		}

		return join(' ', [
			'UPDATE',
			$this->from,
			'SET',
			join(', ', $updateFields),
			$this->getWhereClause()
		]);
	}

	public function execute(): bool {
		return !$this->hasMutations() || parent::execute();
	}
}
