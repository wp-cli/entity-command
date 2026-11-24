<?php
/**
 * Function polyfills for PHP 7.2+ compatibility.
 *
 * This file provides polyfills for PHP functions that are used by WP_Block_Processor
 * but were introduced in PHP 8.0+.
 *
 * @package WP_CLI\Entity\Compat
 */

// Polyfill for str_ends_with() - introduced in PHP 8.0.
if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * Checks if a string ends with a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return bool True if haystack ends with needle, false otherwise.
	 */
	function str_ends_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}
		$len = strlen( $needle );
		return substr( $haystack, -$len ) === $needle;
	}
}

// Polyfill for str_starts_with() - introduced in PHP 8.0.
// Not currently used by WP_Block_Processor but included for completeness.
if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Checks if a string starts with a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return bool True if haystack starts with needle, false otherwise.
	 */
	function str_starts_with( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}
		return 0 === strncmp( $haystack, $needle, strlen( $needle ) );
	}
}

// Polyfill for str_contains() - introduced in PHP 8.0.
// Not currently used by WP_Block_Processor but included for completeness.
if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Checks if a string contains a given substring.
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return bool True if needle is in haystack, false otherwise.
	 */
	function str_contains( string $haystack, string $needle ): bool {
		if ( '' === $needle ) {
			return true;
		}
		return false !== strpos( $haystack, $needle );
	}
}
