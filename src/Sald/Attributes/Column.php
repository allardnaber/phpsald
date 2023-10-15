<?php

namespace Sald\Attributes;

use PDO;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Column {
	public function __construct(int $type = PDO::PARAM_INT, string $name = null) {}
}
