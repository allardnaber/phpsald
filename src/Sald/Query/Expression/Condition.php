<?php

namespace Sald\Query\Expression;

class Condition {

	private string $field;
	private Comparator $comparator;
	private mixed $value;

	public function __construct(string $field, Comparator $comparator, mixed $value = null) {
		$this->field = $field;
		$this->comparator = $comparator;
		$this->value = $value;
	}

	public function getSQL(): string {
		return sprintf('%s %s %s', $this->field, $this->comparator->value, $this->value);
	}
}