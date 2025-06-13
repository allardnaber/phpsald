<?php

namespace Sald\Entities\Mapper;

class GenericResultMapper extends ResultMapper {

	protected function convertRecord(array $record): array {
		return $record;
	}
}
