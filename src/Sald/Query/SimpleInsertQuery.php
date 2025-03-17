<?php

namespace Sald\Query;

class SimpleInsertQuery extends AbstractQuery {

	private array $insertKeys = [];

	public function set(string $key, mixed $value): self {
		$this->insertKeys[] = $key;
		$this->parameter('insert_' . $key, $value);
		return $this;
	}

	protected function buildQuery(): string {
		$keysPrefixed = array_map(fn($e) => ':insert_' . $e, $this->insertKeys);

		return sprintf ('INSERT INTO %s (%s) VALUES (%s)',
			$this->from,
			join(', ', $this->insertKeys),
			join(', ', $keysPrefixed)
		);
	}

	public function execute(): bool {
		$stmt = $this->connection->prepare($this->getSQL());
		$this->bindValues($stmt);

		return $this->connection->execute($stmt);
	}
}