<?php

namespace Sald\Metadata;

class TableMetadata extends AbstractMetadata {

	/**
	 * @var string[]
	 */
	private array $idColumns = [];

	/**
	 * @var ColumnMetadata[]
	 */
	private array $columns = [];

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