<?php

namespace Sald\Exception\Converter;

use PDOException;
use Sald\Exception\Db\Connection\DbConnectionException;
use Sald\Exception\Db\Connection\DbDatabaseDoesNotExistException;
use Sald\Exception\Db\Data\DbColumnDoesNotExistException;
use Sald\Exception\Db\Data\DbConstraintViolationException;
use Sald\Exception\Db\Data\DbDataException;
use Sald\Exception\Db\Data\DbInsufficientPermissionsException;
use Sald\Exception\Db\Data\DbTableDoesNotExistException;
use Sald\Exception\Db\Data\DbTransactionException;
use Sald\Exception\Db\DbException;
use Sald\Exception\Db\System\DbSystemException;

class SqliteErrorConverter implements ErrorConverter {

	// see https://www.sqlite.org/rescode.html
	private const array CONVERSION_TABLE = [
		// 1 => // generic error, #convert makes a distinction based on error message
		 2 => DbSystemException::class,                  // SQLITE_INTERNAL
		 3 => DbInsufficientPermissionsException::class, // SQLITE_PERM
		 4 => DbSystemException::class,                  // SQLITE_ABORT
		 5 => DbTransactionException::class,             // SQLITE_BUSY
		 6 => DbSystemException::class,                  // SQLITE_LOCKED
		 7 => DbSystemException::class,                  // SQLITE_NOMEM
		 8 => DbInsufficientPermissionsException::class, // SQLITE_READONLY
		 9 => DbSystemException::class,                  // SQLITE_INTERRUPT
		10 => DbSystemException::class,                  // SQLITE_IOERR
		11 => DbSystemException::class,                  // SQLITE_CORRUPT
		12 => DbConnectionException::class,              // SQLITE_NOTFOUND
		13 => DbSystemException::class,                  // SQLITE_FULL
		14 => DbConnectionException::class,              // SQLITE_CANTOPEN
		15 => DbSystemException::class,                  // SQLITE_PROTOCOL
		// 16 // SQLITE_EMPTY - not used
		17 => DbDataException::class,                    // SQLITE_SCHEMA
		18 => DbDataException::class,                    // SQLITE_TOOBIG
		19 => DbConstraintViolationException::class,     // SQLITE_CONSTRAINT
		20 => DbDataException::class,                    // SQLITE_MISMATCH
		// 21 // SQLITE_MISUSE - no mapping
		22 => DbSystemException::class,                  // SQLITE_NOLFS
		23 => DbInsufficientPermissionsException::class, // SQLITE_AUTH
		// 24 // SQLITE_FORMAT - not used
		25 => DbDataException::class,                    // SQLITE_RANGE
		26 => DbDatabaseDoesNotExistException::class,    // SQLITE_NOTADB
		// 27/28 - SQLITE_NOTICE/SQLITE_WARNING not used

	];

	public function convert(PDOException $exception, ?string $sqlState = null, ?string $driverCode = null, ?string $driverMessage = null): DbException {
		if ($sqlState === null) return DbException::fromException($exception);
		$errorCode = (int) $driverCode;

		if ($errorCode === 1 && str_contains(strtolower($driverMessage), 'no such table')) {
			$type = DbTableDoesNotExistException::class;
		} else if ($errorCode === 1 && str_contains(strtolower($driverMessage), 'no such column')) {
			$type = DbColumnDoesNotExistException::class;
		} else {
			$type = self::CONVERSION_TABLE[$errorCode] ?? null;
		}

		if ($type !== null) {
			return call_user_func([$type, 'fromException'], $exception);
		} else {
			return DbException::fromException($exception);
		}
	}
}
