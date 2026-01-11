<?php

namespace Sald\Connection\MultiHost;

class HostCache {

	private static bool $cacheAvailable;

	private const string PREFIX = 'mhost_';

	public function __construct(private int $ttl = 60) {}

	private static function isAvailable(): bool {
		if (!isset(self::$cacheAvailable)) {
			self::$cacheAvailable = function_exists('apcu_enabled') && apcu_enabled();
			if (!self::$cacheAvailable) {
				echo "NO CACHE\n";
			} else {
				$x = new \APCUIterator();
				printf("%d items in cache\n", $x->getTotalCount());
			}
		}
		return self::$cacheAvailable;
	}

	/**
	 * @param string $configChecksum
	 * @return string|null dsn is available in cache
	 */
	public function getHostForConfiguration(string $configChecksum): ?string {
		if (!self::isAvailable()) return null;
		/** @noinspection PhpComposerExtensionStubsInspection guarded by {@see self::isAvailable()} */
		$cached = apcu_fetch(self::PREFIX . $configChecksum);
		return $cached === false ? null : $cached;
	}

	public function saveHostForConfiguration(string $configChecksum, string $dsn): void {
		if (!self::isAvailable()) return;
		/** @noinspection PhpComposerExtensionStubsInspection guarded by {@see self::isAvailable() } */
		$res = apcu_store(self::PREFIX . $configChecksum, $dsn, 0);
		print_r($res);
		if ($res === false) { printf("Add to achec failed\n"); } else {
			printf("saved %s to cache key %s\n", $configChecksum, $dsn);
		}
	}





}