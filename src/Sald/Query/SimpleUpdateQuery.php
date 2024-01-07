<?php

namespace Sald\Query;

use Sald\Query\Expression\Expression;

class SimpleUpdateQuery extends AbstractQuery {
	private array $updateKeys = [];
	public function set(string $key, mixed $value): self {
		$this->updateKeys[] = $key;
		$this->parameter('update_' . $key, $value);
		return $this;
	}

	protected function buildQuery(): string {
		$updateFields = [];
		foreach ($this->updateKeys as $key) {
			$parameter = 'update_' . $key;
			if ($this->parameters[$parameter] instanceof Expression) {
				$updateFields[] = sprintf('%s=(%s)', $key, $this->parameters[$parameter]->getExpression());
			} else {
				$updateFields[] = sprintf('%s=:%s', $key, $parameter);
			}
		}

		return join(' ', [
			'UPDATE',
			$this->from,
			'SET',
			join(' ', $updateFields),
			$this->getWhereClause()
		]);
	}

	public function execute(): bool {
		if (count($this->updateKeys) === 0) {
			// Desired state is already in the database.
			return true;
		}

		$stmt = $this->connection->prepare($this->getSQL());
		$this->bindValues($stmt);

		return $stmt->execute();
	}
}
