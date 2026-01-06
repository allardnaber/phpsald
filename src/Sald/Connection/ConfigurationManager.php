<?php

namespace Sald\Connection;

class ConfigurationManager {

	private static ?Configuration $default = null;

	public static function setDefaultConfig(Configuration $config): void {
		self::$default = $config;
	}

	public static function getDefault(): ?Configuration {
		return self::$default;
	}
}
