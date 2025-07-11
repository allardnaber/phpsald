<?php

namespace Sald\Attributes;

use Attribute;
use Sald\Query\Expression\Condition;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany {
	public function __construct(
		private readonly string $classname,
		private readonly string $referencedBy,
		private readonly string $references,
		private readonly ?Condition $condition = null,
		private readonly ?string $tableName = null,
		private readonly ?string $alias = null
	) {}

	/**
	 * @return string
	 */
	public function getClassname(): string {
		return $this->classname;
	}

	/**
	 * @return string
	 */
	public function getReferencedBy(): string {
		return $this->referencedBy;
	}

	/**
	 * @return string
	 */
	public function getReferences(): string {
		return $this->references;
	}

	/**
	 * @return Condition|null
	 */
	public function getCondition(): ?Condition {
		return $this->condition;
	}

	/**
	 * @return string|null
	 */
	public function getTableName(): ?string {
		return $this->tableName;
	}

	/**
	 * @return string|null
	 */
	public function getAlias(): ?string {
		return $this->alias;
	}

}
