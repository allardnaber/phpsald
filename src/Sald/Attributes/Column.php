<?php

namespace Sald\Attributes;

use Attribute;
use Sald\Metadata\ColumnType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column {

	public function __construct(private readonly ?string $name = null, private readonly string $type = ColumnType::UNDEFINED) {}

	public function getColumnName(): string {
		return $this->name;
	}

	public function getColumnType(): string {
		return $this->type;
	}
}
