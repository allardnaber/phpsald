<?php

namespace Sald\Connection;

class ConfigurationManager {

	private static ?Configuration $default = null;

	private static array $configMap = [];

	public static function setDefaultConfig(Configuration $config): void {
		self::$default = $config;
	}

	public static function getDefault(): ?Configuration {
		return self::$default;
	}

	public static function registerConfiguration(Configuration $config): void{
		self::$configMap[$config->getChecksum()] = $config;
	}

	public static function getConfiguration(string $checksum): ?Configuration {
		return self::$configMap[$checksum] ?? null;
	}
}
