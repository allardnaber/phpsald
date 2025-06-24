<?php

namespace Sald\Attributes;

use Sald\Query\Expression\Condition;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class OneToMany {
	public function __construct(
		private string $classname,
		private string $referencedBy,
		private string $references,
		private ?Condition $condition = null,
		private ?string $tableName = null,
		private ?string $alias = null
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
