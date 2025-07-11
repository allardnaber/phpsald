<?php

namespace Sald\Entities\Mapper;

class ResultColumnMetadata {

	public function __construct(private array $metadata) {}

	public function getNativeType(): ?string { return $this->metadata['native_type'] ?? null; }
	public function getPDOType(): ?int { return $this->metadata['pdo_type'] ?? null; }
	public function getLen(): ?int { return $this->metadata['len'] ?? null; }
	public function getTable(): ?string { return $this->metadata['table'] ?? null; }
	public function getName(): ?string { return $this->metadata['name'] ?? null; }
	public function getPrecision(): ?int { return $this->metadata['precision'] ?? null; }
}
