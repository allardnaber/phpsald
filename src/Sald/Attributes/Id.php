<?php

namespace Sald\Attributes;

use PDO;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Id {

	public const AUTO_INCREMENT = 1;

	public function __construct(private int $flags = 0) {}

	public function getFlags(): int {
		return $this->flags;
	}

	public function hasFlag(int $flag): bool {
		return $this->flags & $flag > 0;
	}
}
