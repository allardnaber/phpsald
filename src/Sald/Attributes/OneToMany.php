<?php

namespace Sald\Attributes;

use Attribute;
use Sald\Query\Expression\Condition;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany extends RelationAttribute {
	public function __construct(
		string $classname,
		string $referencedBy,
		string $references,
		?Condition $condition = null,
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
