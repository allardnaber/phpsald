<?php

namespace Sald\Query;

class QueryParameter {

	private string $paramName;

	public function __construct(private readonly string $columnName, private readonly mixed $value, string $type) {
		$this->paramName = $this->generateParameterName($this->columnName, $type);
	}

	public function getParamName(): string {
		return $this->paramName;
	}

	public function getPlaceholderName(): string {
		return ':' . $this->paramName;
	}

	public function getColumnName(): string {
		return $this->columnName;
	}

	public function getValue(): mixed {
		return $this->value;
	}

	/**
	 * Generate a safe parameter name, by eliminating characters that are valid in conditions (i.e. tableName.fieldName),
	 * but not in parameter names.
	 * @param string $columnName
	 * @param string $type
	 * @return string Operation type, to distinguish between selection conditions and update values.
	 */
	private function generateParameterName(string $columnName, string $type): string {
		return sprintf('__%s_%s', $type, str_replace('.', '_', $columnName));
	}

}
