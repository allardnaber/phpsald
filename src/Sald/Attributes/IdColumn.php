<?php

namespace Sald\Attributes;

use PDO;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IdColumn {
	public function __construct(int $type = PDO::PARAM_INT) {}
}
