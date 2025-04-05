<?php

namespace Sald\Exception\Db\Data;

class DbColumnDoesNotExistException extends DbDataException {

	public function getColumnName(): ?string {
		return $this->getFirstQuotedText();
	}
}
