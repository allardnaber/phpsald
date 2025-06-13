<?php

namespace Sald\Entities\Mapper;

use Sald\Exception\InvalidResultException;

class PgsqlResultMapper extends ResultMapper {

	protected function convertRecord(array $record): array {
		foreach ($this->getMetadata() as $column) {
			switch($column->getNativeType()) {
				case 'json':
				case 'jsonb':
					$record[$column->getName()] = json_decode($record[$column->getName()] ?? 'null');
					break;

				default:
					if (str_starts_with($column->getNativeType() ?? '', '_')) {
						$record[$column->getName()] = $this->expandArrayValue($record[$column->getName()]);
					}
			}

		}

		return $record;
	}

	private function expandArrayValue(?string $value): ?array {
		if (empty($value)) {
			return null;
		}

		if (!str_starts_with($value, '{') || !str_ends_with($value, '}')) {
			throw new InvalidResultException(sprintf('Array value for column %s should be enclosed in braces. %s', $column->getName(), $value));
		}

		// @todo multidimensional arrays
		return json_decode('[' . substr($value, 1, -1) . ']');
	}
}
