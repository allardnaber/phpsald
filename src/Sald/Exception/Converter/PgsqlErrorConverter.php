<?php

namespace Sald\Exception\Converter;

use Sald\Exception\Db\Connection\DbConnectionException;
use Sald\Exception\Db\Connection\DbDatabaseDoesNotExistException;
use Sald\Exception\Db\Data\DbColumnDoesNotExistException;
use Sald\Exception\Db\Data\DbConstraintViolationException;
use Sald\Exception\Db\Data\DbDataException;
use Sald\Exception\Db\Data\DbInsufficientPermissionsException;
use Sald\Exception\Db\Data\DbTableDoesNotExistException;
use Sald\Exception\Db\Data\DbTransactionException;
use Sald\Exception\Db\DbException;
use Sald\Exception\Db\Syntax\DbSyntaxException;
use Sald\Exception\Db\System\DbSystemException;

class PgsqlErrorConverter implements ErrorConverter {

	// see https://www.postgresql.org/docs/current/errcodes-appendix.html
	private const CONVERSION_TABLE = [
		5 => [
			'42P01' => DbTableDoesNotExistException::class,
			'42703' => DbColumnDoesNotExistException::class,
			'42501' => DbInsufficientPermissionsException::class,
			'42601' => DbSyntaxException::class,
			//'08006' => DbDatabaseDoesNotExistException::class, // 08006 is used for all connection issues. This cannot be distinguished
		],
		2 => [
			'08' => DbConnectionException::class, // Class 08 — Connection Exception
			'22' => DbDataException::class, // Class 22 — Data Exception
			'23' => DbConstraintViolationException::class, // Class 23 — Integrity Constraint Violation
			'25' => DbTransactionException::class, // Class 25 — Invalid Transaction State
			'40' => DbTransactionException::class, // Class 40 — Transaction Rollback
			'42' => DbDataException::class, // Class 42 — Syntax Error or Access Rule Violation
			'53' => DbSystemException::class, // Class 53 — Insufficient Resources
			'54' => DbSystemException::class, // Class 54 — Program Limit Exceeded
			'57' => DbSystemException::class, // Class 57 — Operator Intervention
			'58' => DbSystemException::class, // Class 58 — System Error (errors external to PostgreSQL itself)
			'XX' => DbSystemException::class, // Class XX — Internal Error
		]
	];

	public function convert(\PDOException $exception, ?string $sqlState = null, ?string $driverCode = null, ?string $driverMessage = null): DbException {
		if ($sqlState === null) return DbException::fromException($exception);
		$lengths = array_keys(self::CONVERSION_TABLE);

		rsort($lengths);
		foreach ($lengths as $length) {
			$code = substr($sqlState, 0, $length);
			if (isset(self::CONVERSION_TABLE[$length][$code])) {
				return call_user_func([self::CONVERSION_TABLE[$length][$code], 'fromException'], $exception);
			}
		}
		return DbException::fromException($exception);
	}
}
