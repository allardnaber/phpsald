<?php

namespace Sald\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Table {
	public function __construct(private readonly string $tableName) {}

	public function getName(): string {
		return $this->tableName;
	}
}
