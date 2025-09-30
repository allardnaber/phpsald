<?php

namespace Sald\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {

	const TYPE_UNDEFINED = 0;
	const TYPE_JSON = 11;
	const TYPE_JSONB = 12;

	public function __construct(private readonly string $name, private readonly int $type = self::TYPE_UNDEFINED) {}

	public function getColumnName(): string {
		return $this->name;
	}

	public function getColumnType(): int {
		return $this->type;
	}
}
