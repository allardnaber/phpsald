<?php

namespace Sald\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {
	public function __construct(private string $name) {}

	public function getColumnName(): string {
		return $this->name;
	}
}
