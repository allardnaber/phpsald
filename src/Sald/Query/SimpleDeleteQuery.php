<?php

namespace Sald\Query;
class SimpleDeleteQuery extends AbstractQuery {

	public function execute(): bool {
		$stmt = $this->connection->prepare($this->getSQL());
		return $stmt->execute();
	}

	protected function buildQuery(): string {
		$q = 'DELETE FROM ' . $this->from;
	
		if (empty($this->where)) {
			throw new \Exception('Attempting to perform an unqualified DELETE. Aborted.');
		}
		else {
			$q .= ' WHERE ';
			$q .= join(' AND ', $this->where);
		}
		
		return $q;
	}

}