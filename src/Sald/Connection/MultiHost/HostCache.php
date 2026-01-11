<?php

/** @noinspection PhpComposerExtensionStubsInspection APCU unavailability is guarded by {@see self::isAvailable() } */
namespace Sald\Connection\MultiHost;

class HostCache {

	private static bool $cacheAvailable;

	private const string PREFIX = 'MHost_';

	public function __construct(private readonly int $ttl = 60) {}

	private static function isAvailable(): bool {
		if (!isset(self::$cacheAvailable)) {
			self::$cacheAvailable = function_exists('apcu_enabled') && apcu_enabled();
		}
		return self::$cacheAvailable;
	}

	/**
	 * @param string $configChecksum
	 * @return string|null dsn is available in cache
	 */
	public function getHostForConfiguration(string $configChecksum): ?string {
		if (!self::isAvailable()) return null;
		$cached = apcu_fetch(self::PREFIX . $configChecksum);
		return $cached === false ? null : $cached;
	}

	public function saveHostForConfiguration(string $configChecksum, string $dsn): void {
		if (!self::isAvailable()) return;
		apcu_store(self::PREFIX . $configChecksum, $dsn, $this->ttl);
	}

	public function deleteHostForConfiguration(string $configChecksum): void {
		if (!self::isAvailable()) return;
		apcu_delete(self::PREFIX . $configChecksum);
	}

}
