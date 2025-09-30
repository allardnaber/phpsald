<?php

namespace Sald\Metadata;

use Sald\Attributes\Column;
use Sald\Attributes\Id;
use Sald\Attributes\OneToMany;

class ColumnMetadata extends AbstractMetadata {

	private bool $isIdColumn = false;
	private ?Id $idAttribute = null;
	private mixed $relation = null;

	private ?string $typeOverride = null;

	public function __construct(string $objectName, private readonly string $type) {
		parent::__construct($objectName);
	}

	public function applyColumnAttribute(Column $attribute): void {
		if ($attribute->getColumnName() !== null) {
			$this->setNameOverride($attribute->getColumnName());
		}
		if ($attribute->getColumnType() !== ColumnType::UNDEFINED) {
			$this->setTypeOverride($attribute->getColumnType());
		}
	}

	public function setTypeOverride(string $override): void {
		$this->typeOverride = $override;
	}

	public function applyIdAttribute(Id $attribute): void {
		$this->isIdColumn = true;
		$this->idAttribute = $attribute;
	}

	public function isAutoIncrement(): bool {
		return $this->idAttribute?->hasFlag(Id::AUTO_INCREMENT) ?? false;
	}

	public function getRealObjectType(): string {
		return $this->type;
	}

	public function getDbObjectType(): string {
		return $this->typeOverride ?? $this->type;
	}

	public function isIdColumn(): bool {
		return $this->isIdColumn;
	}

	public function isEditable(): bool {
		return !$this->isAutoIncrement();
	}

	public function setOneToMany(OneToMany $relation): void {
		$this->relation = $relation;
	}

	public function getRelation(): OneToMany|null {
		return $this->relation;
	}
}
