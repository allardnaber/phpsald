<?php

namespace Sald\Query\Expression;

class Condition extends Expression {

	public function __construct(string $field, Comparator $comparator, mixed $value = null) {
		parent::__construct(sprintf('%s %s %s', $field, $comparator->value, $value));
	}
}
