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
		$parts = [];
		foreach ($this->updateKeys as $key) {
			$fkey = 'update_' . $key;
			if ($this->parameters[$fkey] instanceof Expression) {
				$parts[] = sprintf('%s=(%s)', $key, $this->parameters[$fkey]->getExpression());
			} else {
				$parts[] = sprintf('%s=%s', $key, $fkey);
			}
		}
		$q = sprintf('UPDATE %s SET %s',
			$this->from,
			join(', ', $parts)
		);
	
		if (!empty($this->where)) {
			$q .= ' WHERE ';
			$q .= join(' AND ', $this->where);
		}
		
		return $q;
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
