<?php

namespace Sald\Attributes;

use Sald\Query\Expression\Condition;

class RelationAttribute {

	/**
	 * @param string $classname
	 * @param string $referencedBy
	 * @param string $references
	 * @param Condition|Condition[]|null $condition
	 * @param string|null $tableName
	 * @param string|null $alias
	 * @param bool $deepFetch
	 */
	public function __construct(
		private readonly string $classname,
		private readonly string $referencedBy,
		private readonly string $references,
		private readonly Condition|array|null $condition = null,
		private readonly ?string $tableName = null,
		private readonly ?string $alias = null,
		private readonly bool $deepFetch = true
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
	 * @return Condition|Condition[]|null
	 */
	public function getCondition(): Condition|array|null {
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

	public function getDeepFetch(): bool {
		return $this->deepFetch;
	}
}
