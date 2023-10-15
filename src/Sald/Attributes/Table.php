<?php

namespace Sald\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Table {
	public function __construct(string $tableName) {}
}
