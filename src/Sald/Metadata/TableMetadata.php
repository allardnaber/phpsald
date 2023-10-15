<?php

namespace Sald\Metadata;

use PDO;
use ReflectionClass;
use Sald\Attributes\IdColumn;
use Sald\Attributes\Table;
use Sald\Exception\ClassNotFoundException;

class TableMetadata {
	private string $tableName;
	private string $idColumnName = 'id';
	private int $idColumnType = PDO::PARAM_INT;

	/**
	 * @var string[] list of editable fields
	 */
	private array $editable = [];

	private string $classname;

	public function __construct($classname) {
		try {
			$this->classname = $classname;
			$reflection = new ReflectionClass($classname);
			$this->findTableName($reflection);
			$this->findIdColumn($reflection);
			$this->findEditableFields($reflection);

		} catch (\ReflectionException $e) {
			throw new ClassNotFoundException(
				sprintf('Class %s does not exist and cannot be used as database entity', $classname),
				$e->getCode(), $e);
		}
	}

	public function getTableName(): string { return $this->tableName; }
	public function getIdColumnName(): string { return $this->idColumnName; }
	public function getIdColumnType(): int { return $this->idColumnType; }
	public function getClassname(): string { return $this->classname; }

	/**
	 * @return string[]
	 */
	public function getEditableFields(): array { return $this->editable; }

	private function findTableName(ReflectionClass $reflection): void {
		$this->tableName = $reflection->getShortName(); // use as backup
		$tableNameAttr = $reflection->getAttributes(Table::class);
		if (count($tableNameAttr) > 0) {
			$arguments = $tableNameAttr[0]->getArguments();
			if (count($arguments) > 0) {
				$this->tableName = $arguments[0];
			}
		}
	}

	private function findIdColumn(ReflectionClass $reflection): void {
		foreach ($reflection->getProperties() as $property) {
			$idColumnAttr = $property->getAttributes(IdColumn::class);
			if (count($idColumnAttr) > 0) {
				$this->idColumnName = $property->getName();
				$arguments = $idColumnAttr[0]->getArguments();
				$this->idColumnType = count($arguments) > 0 ? $arguments[0] : PDO::PARAM_INT;
			}
		}
	}

	private function findEditableFields(ReflectionClass $reflection): void {
		foreach ($reflection->getProperties() as $property) {
			if ($property->getName() !== $this->idColumnName) {
				$this->editable[] = $property->getName();
			}
		}
	}

}