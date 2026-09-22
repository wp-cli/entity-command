<?php
/**
 * PHPUnit bootstrap file for entity-command tests.
 *
 * These tests run WITHOUT WordPress - they test the polyfills and helper
 * classes in isolation. For integration tests with WordPress, use Behat.
 *
 * @package WP_CLI\Entity
 */

// Load Composer autoloader.
$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
}

// Global stub for add_filter when running unit tests outside WordPress.
if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Stub add_filter for isolated unit testing.
	 *
	 * @param string   $hook_name     The filter hook name.
	 * @param callable $callback      The callback function.
	 * @param int      $priority      Optional. Priority. Default 10.
	 * @param int      $accepted_args Optional. Accepted arguments. Default 1.
	 * @return true
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Mocking WordPress core function for isolated testing.
	function add_filter( $hook_name, $callback, $priority = 10, $accepted_args = 1 ) {
		global $wpcli_entity_filter_test_log;
		if ( ! is_array( $wpcli_entity_filter_test_log ) ) {
			$wpcli_entity_filter_test_log = [];
		}
		$wpcli_entity_filter_test_log[] = [
			'hook_name'     => $hook_name,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		];
		return true;
	}
}

// Load the polyfills - these are what we're testing.
// Note: We load polyfills.php directly to ensure our polyfill functions are defined
// even if the native PHP functions exist.
require_once dirname( __DIR__ ) . '/src/Compat/polyfills.php';
require_once dirname( __DIR__ ) . '/src/Compat/WP_HTML_Span.php';
require_once dirname( __DIR__ ) . '/src/Compat/WP_Block_Processor.php';

// Load the Block_Processor_Helper class.
require_once dirname( __DIR__ ) . '/src/Block_Processor_Helper.php';
