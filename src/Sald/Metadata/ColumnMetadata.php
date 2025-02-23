<?php

namespace Sald\Metadata;

use Sald\Attributes\Column;
use Sald\Attributes\Id;

class ColumnMetadata {

	private bool $isIdColumn = false;
	private ?Id $idAttribute = null;
	private ?string $columnNameOverride = null;

	public function __construct(private string $propertyName, private string $type) {}

	public function applyColumnAttribute(Column $attribute): void {
		$this->columnNameOverride = $attribute->getColumnName();
	}

	public function applyIdAttribute(Id $attribute): void {
		$this->isIdColumn = true;
		$this->idAttribute = $attribute;
	}

	public function isAutoIncrement(): bool {
		return $this->idAttribute?->hasFlag(Id::AUTO_INCREMENT) ?? false;
	}

	public function getColumnName(): string {
		return $this->columnNameOverride ?? $this->propertyName;
	}

	public function getPropertyName(): string {
		return $this->propertyName;
	}

	public function getType(): string {
		return $this->type;
	}

	public function isIdColumn(): bool {
		return $this->isIdColumn;
	}

	public function isEditable(): bool {
		return !$this->isAutoIncrement();
	}
}
