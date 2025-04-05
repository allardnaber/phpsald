<?php

namespace Sald\Metadata;

use Sald\Attributes\Column;
use Sald\Attributes\Id;

class ColumnMetadata extends AbstractMetadata {

	private bool $isIdColumn = false;
	private ?Id $idAttribute = null;

	public function __construct(string $objectName, private string $type) {
		parent::__construct($objectName);
	}

	public function applyColumnAttribute(Column $attribute): void {
		$this->setNameOverride($attribute->getColumnName());
		// @todo include custom column types (json, date, etc)
	}

	public function applyIdAttribute(Id $attribute): void {
		$this->isIdColumn = true;
		$this->idAttribute = $attribute;
	}

	public function isAutoIncrement(): bool {
		return $this->idAttribute?->hasFlag(Id::AUTO_INCREMENT) ?? false;
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
