<?php

namespace Sald\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Id {

	public const AUTO_INCREMENT = 1;

	public function __construct(private readonly int $flags = 0) {}

	public function hasFlag(int $flag): bool {
		return $this->flags & $flag > 0;
	}
}
