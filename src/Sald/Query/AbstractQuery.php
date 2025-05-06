<?php

namespace Sald\Query;

use PDO;
use PDOStatement;
use Sald\Connection\Connection;
use Sald\Exception\IncompleteDataException;
use Sald\Metadata\TableMetadata;
use Sald\Query\Expression\Comparator;
use Sald\Query\Expression\Condition;
use Sald\Query\Expression\Expression;

abstract class AbstractQuery {

	protected string $from;
	private string $query;
	private bool $dirty = true;
	/**
	 * @var Condition[]
	 */
	protected array $where = [];

	/**
	 * @var QueryParameter[]
	 */
	protected array $parameters = [];

	protected TableMetadata $tableMetadata;

	protected string $classname;
	protected Connection $connection;

	public function __construct(Connection $connection, TableMetadata $metadata) {
		$this->connection = $connection;
		$this->tableMetadata = $metadata;
		$this->classname = $metadata->getRealObjectName();
		$this->from = $metadata->getDbObjectName();
	}

	abstract protected function buildQuery(): string;

	public function getSQL(): string {
		if ($this->dirty) {
			$this->query = $this->buildQuery();
			$this->dirty = false;
		}
		return $this->query;
	}

	public function overrideTableName(string $tableName): self {
		$this->from = $tableName;
		return $this;
	}

	public function whereLiteral(string $where): self {
		$this->setDirty();
		$this->where[] = new Expression($where);
		return $this;
	}

	public function where(string $field, mixed $value, Comparator $comparator = Comparator::EQ): self {
		$this->setDirty();
		$columnName = $this->tableMetadata->getColumn($field)?->getDbObjectName() ?? $field;

		if ($value instanceof Expression) {
			$insertVal = $value;
		} else {
			$this->parameter($field, $value);
			$insertVal = $this->parameters[$field]->getPlaceholderName();
		}

		$this->where[] = new Condition($columnName, $comparator, $insertVal);
		return $this;
	}

	public function whereId(mixed $value, Comparator $comparator = Comparator::EQ): self {
		$idFields = $this->tableMetadata->getIdColumns();
		if (count($idFields) > 1 && !is_array($value)) {
			throw new IncompleteDataException(sprintf('%d id fields are required, use an array argument to provide the full id.', count($idFields)));
		}
		if (!is_array($value)) {
			return $this->where($idFields[0], $value, $comparator);
		} else {
			foreach ($idFields as $key) {
				$objectName = $this->tableMetadata->getColumn($key)->getDbObjectName();
				if (!isset($value[$objectName])) {
					throw new IncompleteDataException(sprintf('Id field should contain a value for %s.', $key));
				}
				$this->where($key, $value[$objectName], $comparator);
			}
			return $this;
		}
	}

	protected function getWhereClause(): string {
		if (empty($this->where)) {
			return '';
		}
		return 'WHERE ' . join(' AND ', array_map(fn(Expression $e) => $e->getSQL(), $this->where));
	}

	public function parameter(string $key, mixed $value): self {
		$this->parameters[$key] = new QueryParameter($key, $value, 'where');
		return $this;
	}


	protected function setDirty(): void {
		$this->dirty = true;
	}

	protected function bindValues(PDOStatement $statement): void {
		$this->bindValuesFromQueryParams($statement, $this->parameters);
	}

	/**
	 * @param PDOStatement $statement
	 * @param QueryParameter[] $params
	 * @return void
	 */
	protected function bindValuesFromQueryParams(PDOStatement $statement, array $params): void {
		foreach ($params as $param) {
			if (!($param->getValue() instanceof Expression)) {
				$statement->bindValue($param->getParamName(), $param->getValue(), $this->getTranslatedPdoType($param->getValue()));
			}
		}
	}

	private function getTranslatedPdoType(mixed $value): int {
		return match (gettype($value)) {
			'NULL' => PDO::PARAM_NULL,
			'boolean' => PDO::PARAM_BOOL,
			'integer' => PDO::PARAM_INT,
			default => PDO::PARAM_STR,
		};
	}

}
