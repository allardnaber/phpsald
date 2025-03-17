<?php

namespace Sald\Exception\Db\Data;

class DbTableDoesNotExistException extends DbDataException {

	public function getTableName(): ?string {
		return $this->getFirstQuotedText();
	}
}
