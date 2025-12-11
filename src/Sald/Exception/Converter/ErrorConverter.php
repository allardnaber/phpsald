<?php

namespace Sald\Exception\Converter;

use PDOException;
use Sald\Exception\Db\DbException;

interface ErrorConverter {

	public function convert(PDOException $exception, ?string $sqlState = null, ?string $driverCode = null, ?string $driverMessage = null): DbException;

}
