<?php

namespace Sald\Query;

class SimpleUpdateQuery extends AbstractQuery {
	private array $updateKeys = [];
	public function set(string $key, mixed $value): self {
		$this->updateKeys[] = $key;
		$this->parameter('update_' . $key, $value);
		return $this;
	}

	protected function buildQuery(): string {
		$parts = array_map(fn($e) => $e . '=:update_' . $e , $this->updateKeys);
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
