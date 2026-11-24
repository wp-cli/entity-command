<?php
/**
 * Loader for WP_Block_Processor polyfills.
 *
 * This class handles conditional loading of polyfills for WordPress block processing
 * classes that may not be available in older versions of WordPress.
 *
 * @package WP_CLI\Entity\Compat
 */

namespace WP_CLI\Entity\Compat;

/**
 * Handles loading of polyfill classes for block processing.
 *
 * The polyfills provide compatibility for:
 * - WP_Block_Processor (WordPress 6.9+)
 * - WP_HTML_Span (WordPress 6.2+)
 * - str_ends_with() function (PHP 8.0+)
 */
class BlockProcessorLoader {

	/**
	 * Whether the polyfills have been loaded.
	 *
	 * @var bool
	 */
	private static $loaded = false;

	/**
	 * Loads the polyfill classes if they haven't been loaded already.
	 *
	 * This method is idempotent - calling it multiple times has no effect
	 * after the first call.
	 *
	 * The loading order is important:
	 * 1. Function polyfills (str_ends_with) - needed by WP_Block_Processor
	 * 2. WP_HTML_Span - dependency of WP_Block_Processor
	 * 3. WP_Block_Processor - main class
	 *
	 * Each polyfill file checks if the class/function already exists before
	 * defining it, so this is safe to call even after WordPress loads the
	 * native classes.
	 *
	 * @return void
	 */
	public static function load(): void {
		if ( self::$loaded ) {
			return;
		}
		self::$loaded = true;

		// Load function polyfills first (str_ends_with for PHP < 8.0).
		// This MUST be loaded before WP_Block_Processor as it uses str_ends_with().
		require_once __DIR__ . '/polyfills.php';

		// Load WP_HTML_Span polyfill if not provided by WordPress.
		// This is a dependency of WP_Block_Processor.
		if ( ! class_exists( 'WP_HTML_Span', false ) ) {
			require_once __DIR__ . '/WP_HTML_Span.php';
		}

		// Load WP_Block_Processor polyfill if not provided by WordPress.
		if ( ! class_exists( 'WP_Block_Processor', false ) ) {
			require_once __DIR__ . '/WP_Block_Processor.php';
		}
	}

	/**
	 * Checks if the polyfills have been loaded.
	 *
	 * @return bool True if load() has been called, false otherwise.
	 */
	public static function is_loaded(): bool {
		return self::$loaded;
	}

	/**
	 * Resets the loaded state for testing purposes.
	 *
	 * WARNING: This is intended for testing only. Do not use in production code.
	 *
	 * @return void
	 */
	public static function reset_for_testing(): void {
		self::$loaded = false;
	}
}
