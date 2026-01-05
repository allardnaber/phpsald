<?php

namespace Sald\Connection;

use Sald\Connection\MultiHost\MultiHostConnection;
use Sald\Log;

/**
 * Factory to create a Sald database Connection. It allows for a distinction between single or multi-host connections.
 * pgsql:port=5432;host=127.0.0.1;dbname=database;sslmode=allow;gssencmode=disable;
 * pgsql:port=5432;host=db1.example.org,db2.example.org;dbname=database;sslmode=allow;gssencmode=disable;
 * pgsql:host=db1.example.org:5432,db2.example.org:5400;dbname=database;sslmode=allow;gssencmode=disable;
 */
class ConnectionFactory {

	public static function create(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null, ?string $schema = null): Connection {
		if (preg_match('/(^[a-z]+:|;)host=[^;]+,/i', $dsn)) {
			// multi host, create specialized connections.
			Log::debug(sprintf('Using multi host chooser for DSN %s.', $dsn));
			return MultiHostConnection::create($dsn, $username, $password, $options, $schema);
		}

		Log::debug(sprintf('Using simple connection for DSN %s.', $dsn));
		return new Connection($dsn, $username, $password, $options, $schema);
	}
}
