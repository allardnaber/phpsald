<?php

namespace Sald\Query\Expression;

class Expression {

	private string $expression;

	public function __construct(string $expression) {
		$this->expression = $expression;
	}

	public function getExpression(): string {
		return $this->expression;
	}

	public function getSQL(): string {
		return $this->expression;
	}
}