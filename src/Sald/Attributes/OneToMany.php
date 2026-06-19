<?php

namespace Sald\Attributes;

use Attribute;
use Sald\Query\Expression\Condition;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany extends RelationAttribute {

	/**
	 * @param string $classname
	 * @param string $referencedBy
	 * @param string $references
	 * @param Condition|Condition[]|null $condition
	 * @param string|null $tableName
	 * @param string|null $alias
	 * @param string|array|null $orderBy
	 */
	public function __construct(
		string $classname,
		string $referencedBy,
		string $references,
		Condition|array|null $condition = null,
		?string $tableName = null,
		?string $alias = null,
		private readonly null|string|array $orderBy = null
	) {
		parent::__construct($classname, $referencedBy, $references, $condition, $tableName, $alias);
	}
	
	/**
	 * @return string|string[]|null
	 */
	public function getOrderBy(): null|string|array {
		return $this->orderBy;
	}

}
