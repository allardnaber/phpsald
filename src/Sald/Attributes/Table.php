<?php

namespace Sald\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Table {
	public function __construct(private string $tableName) {}

	public function getName(): string {
		return $this->tableName;
	}
}
