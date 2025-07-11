<?php

namespace Sald\Exception\Db;

use PDOException;
use RuntimeException;

class DbException extends RuntimeException {

	private array $errorInfo;

	public static function fromException(PDOException $e): static {
		$result = new static($e->getMessage(), 0, $e);
		$result->errorInfo = $e->errorInfo;
		return $result;
	}

	public function getSqlStateError(): ?string {
		return $this->errorInfo[0] ?? null;
	}

	public function getDriverErrorCode(): ?string {
		return $this->errorInfo[1] ?? null;
	}

	public function getDriverErrorMessage(): ?string {
		return $this->errorInfo[2] ?? null;
	}

	/* @todo: this is postgres specific */
	protected function getFirstQuotedText(): ?string {
		if (preg_match('/["«]([^\n\r"\'«»]+)["»]/U', $this->getDriverErrorMessage(), $match)) {
			return trim($match[1]);
		}
		return null;
	}
}
