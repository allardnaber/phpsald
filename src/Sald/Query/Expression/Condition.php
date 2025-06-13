<?php

namespace Sald\Query\Expression;

class Condition extends Expression {

	public function __construct(string $field, Comparator|Operator $comparator, mixed $value = null) {

		if ($comparator instanceof Comparator) {
			parent::__construct(sprintf('%s %s %s', $field, $comparator->value, $value));
		} else { //if ($comparator instanceof Operator) {
			parent::__construct(sprintf('%s = %s (%s)', $field, $comparator->value, $value));
		}
	}
}
