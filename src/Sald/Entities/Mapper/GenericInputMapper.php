<?php

namespace Sald\Entities\Mapper;

use Sald\Metadata\ColumnType;
use Sald\Query\QueryParameter;

class GenericInputMapper extends InputMapper {

	public function convertInput(QueryParameter $param): mixed {
		$value = $param->getValue();

		if ($value instanceof \UnitEnum) {
			return $value->name;
		}

		$column = $this->getTableMetadata()->getColumn($param->getColumnName());
		if ($column !== null && $column->getDbObjectType() === ColumnType::JSON) {
			return json_encode($value);
		}


		// no translation needed
		return $value;
	}

}
