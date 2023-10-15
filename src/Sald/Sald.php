<?php

namespace Sald;

use Sald\Connection\Configuration;
use Sald\Connection\Connection;
use Sald\Connection\ConnectionManager;

class Sald {

	public static function get(?Configuration $config): Connection {
		return ConnectionManager::get($config);
	}

}