<?php

namespace Sald\Metadata;

abstract class AbstractMetadata {

	private ?string $nameOverride = null;

	public function __construct(private readonly string $objectName) {}

	public function setNameOverride(string $override): void {
		$this->nameOverride = $override;
	}

	public function getRealObjectName(): string {
		return $this->objectName;
	}

	public function getDbObjectName(): string {
		return $this->nameOverride ?? $this->objectName;
	}
}
