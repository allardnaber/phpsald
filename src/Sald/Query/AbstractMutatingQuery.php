<?php

namespace Sald\Query;

abstract class AbstractMutatingQuery extends AbstractQuery {

	/**
	 * @var QueryParameter[]
	 */
	private array $mutations = [];

	public function set(string $key, mixed $value): self {
		$this->mutations[] = new QueryParameter($key, $value, 'write');
		return $this;
	}

	public function execute(): bool {
		$stmt = $this->connection->prepare($this->getSQL());
		$this->bindValues($stmt);
		$this->bindValuesFromQueryParams($stmt, $this->mutations);

		return $this->connection->execute($stmt);
	}

	/**
	 * @return QueryParameter[]
	 */
	protected function getMutations(): array {
		return $this->mutations;
	}

	protected function hasMutations(): bool {
		return !empty($this->mutations);
	}
}
