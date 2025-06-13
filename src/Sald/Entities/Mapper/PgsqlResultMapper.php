<?php

namespace Sald\Entities\Mapper;

use Sald\Exception\InvalidResultException;

class PgsqlResultMapper extends ResultMapper {

	protected function convertRecord(array $record): array {
		foreach ($this->getMetadata() as $column) {
			if (str_starts_with($column->getNativeType() ?? '', '_')) {
				$value = $record[$column->getName()] ?? null;
				if ($value !== null) {
					if (!str_starts_with($value, '{') || !str_ends_with($value, '}')) {
						throw new InvalidResultException(sprintf('Array value for column %s should be enclosed in braces. %s', $column->getName(), $value));
					}
					// @todo multidimensional arrays
					$record[$column->getName()] = empty($value) ? null : json_decode('[' . substr($value, 1, -1) . ']');
				}
			}
		}

		return $record;
	}
}
