<?php

namespace Sald\Query\Expression;

readonly class OrderOptions {

	public const string Asc = 'ASC';
	public const string Desc = 'DESC';

	public function __construct(
		private ?string $collation = null,
		private ?string $direction = null,
		private bool $caseInsensitive = false
	) { }

	public static function Literal(): self {
		return new self('C');
	}

	public static function CaseInsensitive(): self {
		return new self(caseInsensitive: true);
	}

	public static function LiteralInsensitive(): self {
		return new self('C', caseInsensitive: false);
	}

	public function getSql(): string {
		$collate = [];
		if ($this->collation !== null) {
			$collate[] = sprintf('"%s"', $this->collation);
		}
		if ($this->caseInsensitive) {
			$collate[] = 'NOCASE';
		}
		return
			(!empty($collate) ? sprintf(' COLLATE %s ', join(' ', $collate)) : '') .
			$this->direction;
	}
}
