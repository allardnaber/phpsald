<?php

namespace Sald\Metadata;

class TableMetadata {

	/**
	 * @var string[]
	 */
	private array $idColumns = [];

	/**
	 * @var ColumnMetadata[]
	 */
	private array $columns = [];

	public function __construct(private string $className, private string $tableName) {}

	public function getClassName(): string { return $this->className; }
	public function getTableName(): string { return $this->tableName; }

	/**
	 * @return string[]
	 */
	public function getIdColumns(): array { return $this->idColumns; }

	/**
	 * @param string[] $idColumns
	 * @return void
	 */
	public function setIdColumns(array $idColumns): void {
		$this->idColumns = $idColumns;
	}

	/**
	 * @param ColumnMetadata[] $columns
	 * @return void
	 */
	public function setColumns(array $columns): void {
		$this->columns = $columns;
	}

	public function getColumn(string $name): ?ColumnMetadata {
		return $this->columns[$name] ?? null;
	}

	/**
	 * @return ColumnMetadata[]
	 */
	public function getColumns(): array {
		return $this->columns;
	}

}