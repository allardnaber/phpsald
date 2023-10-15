<?php

namespace Sald\Entities;

use Sald\Connection\Connection;

class Entity {

	private Connection $connection;
	private array $fields = [];

	public function __construct(Connection $connection, array $fields = []) {
		$this->connection = $connection;
		foreach ($fields as $key => $value) {
			$this->fields[$key] = $value;
		}
	}
}