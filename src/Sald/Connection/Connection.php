<?php

namespace Sald\Connection;

use PDO;
use PDOException;
use PDOStatement;
use Sald\Entities\Entity;
use Sald\Entities\Mapper\ResultMapper;
use Sald\Exception\Converter\DbErrorHandler;
use Sald\Exception\RecordNotFoundException;
use Sald\Metadata\MetadataManager;
use Sald\Metadata\TableMetadata;
use Sald\Query\EntityQueryFactory;
use Sald\Query\SimpleDeleteQuery;
use Sald\Query\SimpleInsertQuery;
use Sald\Query\SimpleSelectQuery;
use Sald\Query\SimpleUpdateQuery;
use SensitiveParameter;

class Connection extends PDO {

	public function __construct(
		string                        $dsn,
		?string                       $username = null,
		#[SensitiveParameter] ?string $password = null,
		?array $options = null,
		?string $schema = null
	) {
		$options = $options ?? [];
		$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
		parent::__construct($dsn, $username, $password, $options);
		$this->setSchema($schema);
	}

	private function setSchema(?string $schema): void {
		if ($schema !== null) {
			$stmt = $this->prepare(sprintf('SET search_path to %s', $schema));
			$stmt->execute();
		}
	}

	/**
	 * @template T
	 * @param class-string<T> $className
	 * @return SimpleSelectQuery<T>
	 */
	public function select(string $className): SimpleSelectQuery {
		return new SimpleSelectQuery($this, $this->getMetadata($className));
	}

	public function insert(string $className): SimpleInsertQuery {
		return new SimpleInsertQuery($this, $this->getMetadata($className));
	}

	public function update(string $className): SimpleUpdateQuery {
		return new SimpleUpdateQuery($this, $this->getMetadata($className));
	}

	public function delete(string $className): SimpleDeleteQuery {
		return new SimpleDeleteQuery($this, $this->getMetadata($className));
	}

	public function insertEntity(Entity $entity): SimpleInsertQuery {
		return EntityQueryFactory::insert($this,  $this->getMetadata($entity::class), $entity);
	}

	public function updateEntity(Entity $entity): SimpleUpdateQuery {
		return EntityQueryFactory::update($this,  $this->getMetadata($entity::class), $entity);
	}

	public function deleteEntity(Entity $entity): SimpleDeleteQuery {
		return EntityQueryFactory::delete($this, $this->getMetadata($entity::class), $entity);
	}

	private function getMetadata(string $className): TableMetadata {
		return MetadataManager::getTable($className);
	}

	/**
	 * Returns all entities that the query returned.
	 * @template T extends Entity
	 * @param PDOStatement $statement The (already executed) statement for which te retrieve the results.
	 * @param class-string<T> $classname The classname for the instances to return.
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                               objects, 'false' none and with an array only the objects linked to the included
	 *                               property names will be fetched.
	 * @return T[] The entities that were returned by the query.
	 */
	public function fetchAll(PDOStatement $statement, string $classname, array|bool $deepFetch = true): array {
		try {
			return ResultMapper::get($this, $statement)->fetchAll($classname, $deepFetch);
		}
		catch (PDOException $e) {
			throw DbErrorHandler::getDbExceptionWithConnection($e, $this);
		}
	}

	/**
	 * Fetches a single record, accepts precisely one record to be returned and will throw an exception otherwise.
	 * @template T extends Entity
	 * @param PDOStatement $statement The (already executed) statement for which te retrieve the result.
	 * @param class-string<T> $classname The classname for the instance to return.
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                               objects, 'false' none and with an array only the objects linked to the included
	 *                               property names will be fetched.
	 * @return T The first instance of Entity
	 * @throws RecordNotFoundException If the record is not found.
	 */
	public function fetchSingle(PDOStatement $statement, string $classname, array|bool $deepFetch = true): Entity {
		return $this->fetchOneRecord($statement, $classname, true, $deepFetch);
	}

	/**
	 * Fetches a single record (the first if the query returns multiple records) or null if no records are available.
	 * @template T extends Entity
	 * @param PDOStatement $statement The (already executed) statement for which te retrieve the result.
	 * @param class-string<T> $classname The classname for the instance to return.
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                               objects, 'false' none and with an array only the objects linked to the included
	 *                               property names will be fetched.
	 * @return T|null Null if the query did not return any records, the first instance of Entity otherwise.
	 */
	public function fetchFirst(PDOStatement $statement, string $classname, array|bool $deepFetch = true): ?Entity {
		return $this->fetchOneRecord($statement, $classname, false, $deepFetch);
	}

	public function execute(PDOStatement $statement, ?array $params = null): bool {
		try {
			return $statement->execute($params);
		} catch (PDOException $e) {
			throw DbErrorHandler::getDbExceptionWithConnection($e, $this);
		}
	}

	/**
	 * @template T extends Entity
	 * @param PDOStatement $statement
	 * @param class-string<T> $classname
	 * @param bool $strict Whether to throw an exception if no record was found.
	 * @param array|bool $deepFetch Controls which related objects should be fetched. 'True' fetches all related
	 *                                objects, 'false' none and with an array only the objects linked to the included
	 *                                property names will be fetched.
	 * @return T|null
	 */

	private function fetchOneRecord(PDOStatement $statement, string $classname, bool $strict, array|bool $deepFetch = true): ?Entity {
		try {
			$result = ResultMapper::get($this, $statement)->fetch($classname, $deepFetch);
		} catch (PDOException $e) {
			throw DbErrorHandler::getDbExceptionWithConnection($e, $this);
		}

		if ($result === false) {
			if ($strict) {
				throw new RecordNotFoundException(
					sprintf('Required record for %s was not found.', $classname));
			} else {
				return null;
			}
		} else {
			return $result;
		}
	}

}
