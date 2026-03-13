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

// Load the polyfills - these are what we're testing.
// Note: We load polyfills.php directly to ensure our polyfill functions are defined
// even if the native PHP functions exist.
require_once dirname( __DIR__ ) . '/src/Compat/polyfills.php';
require_once dirname( __DIR__ ) . '/src/Compat/WP_HTML_Span.php';
require_once dirname( __DIR__ ) . '/src/Compat/WP_Block_Processor.php';

// Load the Block_Processor_Helper class.
require_once dirname( __DIR__ ) . '/src/Block_Processor_Helper.php';
