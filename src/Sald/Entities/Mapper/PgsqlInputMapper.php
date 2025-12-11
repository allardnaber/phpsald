<?php

namespace Sald\Entities\Mapper;

use Sald\Query\QueryParameter;

class PgsqlInputMapper extends GenericInputMapper {

	public function convertInput(QueryParameter $param): mixed {
		$result = parent::convertInput($param);
		// @todo; array values for other drivers?
		return is_array($result) ? $this->convertArrayToString($result) : $result;

	}

	private function convertArrayToString(array $array): string {
		return sprintf('{%s}',
			join(',',
				array_map(fn($val) => is_numeric($val) ? $val : sprintf("'%s'", addslashes($val ?? '')),
					$array)));
	}
}
