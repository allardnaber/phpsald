<?php

namespace Sald\Entities\Mapper;

use Sald\Metadata\ColumnType;
use Sald\Query\QueryParameter;

class GenericInputMapper extends InputMapper {

	public function convertInput(QueryParameter $param): mixed {
		$column = $this->getTableMetadata()->getColumn($param->getColumnName());
		if ($column !== null && $column->getDbObjectType() === ColumnType::JSON) {
			return json_encode($param->getValue());
		}

		// no translation needed
		return $param->getValue();
	}

}
