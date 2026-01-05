<?php

namespace Sald;

use Psr\Log\LoggerInterface;

class Log {

	private static ?LoggerInterface $logger = null;

	public static function setLogger(?LoggerInterface $logger): void {
		self::$logger = $logger;
	}

	public static function hasLogger(): bool {
		return self::$logger !== null;
	}

	public static function emergency(string|\Stringable $message, array $context = []): void {
		self::$logger?->emergency($message, $context);
	}

	public static function alert(string|\Stringable $message, array $context = []): void {
		self::$logger?->alert($message, $context);
	}

	public static function critical(string|\Stringable $message, array $context = []): void {
		self::$logger?->critical($message, $context);
	}

	public static function error(string|\Stringable $message, array $context = []): void {
		self::$logger?->error($message, $context);
	}

	public static function warning(string|\Stringable $message, array $context = []): void {
		self::$logger?->warning($message, $context);
	}

	public static function notice(string|\Stringable $message, array $context = []): void {
		self::$logger?->notice($message, $context);
	}

	public static function debug(string|\Stringable $message, array $context = []): void {
		self::$logger?->debug($message, $context);
	}

	public static function info(string|\Stringable $message, array $context = []): void {
		self::$logger?->info($message, $context);
	}

	public function log($level, string|\Stringable $message, array $context = []): void {
		self::$logger?->log($level, $message, $context);
	}

}
