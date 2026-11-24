<?php
/**
 * Tests for PHP function polyfills.
 *
 * @package WP_CLI\Entity\Compat
 */

namespace WP_CLI\Entity\Tests\Compat;

use PHPUnit\Framework\TestCase;

/**
 * Test the PHP function polyfills.
 *
 * Note: These tests verify the polyfill behavior. On PHP 8.0+, the native
 * functions will be used instead, but they should behave identically.
 */
class PolyfillsTest extends TestCase {

	/**
	 * Test str_ends_with with matching suffix.
	 */
	public function test_str_ends_with_returns_true_for_matching_suffix() {
		$this->assertTrue( str_ends_with( 'hello world', 'world' ) );
		$this->assertTrue( str_ends_with( 'hello world', 'd' ) );
		$this->assertTrue( str_ends_with( 'hello world', 'hello world' ) );
	}

	/**
	 * Test str_ends_with with non-matching suffix.
	 */
	public function test_str_ends_with_returns_false_for_non_matching_suffix() {
		$this->assertFalse( str_ends_with( 'hello world', 'hello' ) );
		$this->assertFalse( str_ends_with( 'hello world', 'World' ) ); // Case sensitive.
		$this->assertFalse( str_ends_with( 'hello world', 'xyz' ) );
	}

	/**
	 * Test str_ends_with with empty needle.
	 */
	public function test_str_ends_with_returns_true_for_empty_needle() {
		$this->assertTrue( str_ends_with( 'hello world', '' ) );
		$this->assertTrue( str_ends_with( '', '' ) );
	}

	/**
	 * Test str_ends_with with empty haystack.
	 */
	public function test_str_ends_with_handles_empty_haystack() {
		$this->assertFalse( str_ends_with( '', 'hello' ) );
	}

	/**
	 * Test str_ends_with with needle longer than haystack.
	 */
	public function test_str_ends_with_handles_needle_longer_than_haystack() {
		$this->assertFalse( str_ends_with( 'hi', 'hello world' ) );
	}

	/**
	 * Test str_ends_with with special characters.
	 */
	public function test_str_ends_with_handles_special_characters() {
		$this->assertTrue( str_ends_with( '<!-- wp:paragraph -->', '-->' ) );
		$this->assertTrue( str_ends_with( '<!-- wp:paragraph -->', '<!-- wp:paragraph -->' ) );
		$this->assertTrue( str_ends_with( 'test<!-', '<!-' ) );
		$this->assertTrue( str_ends_with( 'test<', '<' ) );
	}

	/**
	 * Test str_starts_with with matching prefix.
	 */
	public function test_str_starts_with_returns_true_for_matching_prefix() {
		$this->assertTrue( str_starts_with( 'hello world', 'hello' ) );
		$this->assertTrue( str_starts_with( 'hello world', 'h' ) );
		$this->assertTrue( str_starts_with( 'hello world', 'hello world' ) );
	}

	/**
	 * Test str_starts_with with non-matching prefix.
	 */
	public function test_str_starts_with_returns_false_for_non_matching_prefix() {
		$this->assertFalse( str_starts_with( 'hello world', 'world' ) );
		$this->assertFalse( str_starts_with( 'hello world', 'Hello' ) ); // Case sensitive.
	}

	/**
	 * Test str_starts_with with empty needle.
	 */
	public function test_str_starts_with_returns_true_for_empty_needle() {
		$this->assertTrue( str_starts_with( 'hello world', '' ) );
		$this->assertTrue( str_starts_with( '', '' ) );
	}

	/**
	 * Test str_contains with matching substring.
	 */
	public function test_str_contains_returns_true_for_matching_substring() {
		$this->assertTrue( str_contains( 'hello world', 'world' ) );
		$this->assertTrue( str_contains( 'hello world', 'hello' ) );
		$this->assertTrue( str_contains( 'hello world', 'o w' ) );
		$this->assertTrue( str_contains( 'hello world', 'hello world' ) );
	}

	/**
	 * Test str_contains with non-matching substring.
	 */
	public function test_str_contains_returns_false_for_non_matching_substring() {
		$this->assertFalse( str_contains( 'hello world', 'xyz' ) );
		$this->assertFalse( str_contains( 'hello world', 'World' ) ); // Case sensitive.
	}

	/**
	 * Test str_contains with empty needle.
	 */
	public function test_str_contains_returns_true_for_empty_needle() {
		$this->assertTrue( str_contains( 'hello world', '' ) );
		$this->assertTrue( str_contains( '', '' ) );
	}

	/**
	 * Test str_contains with empty haystack.
	 */
	public function test_str_contains_handles_empty_haystack() {
		$this->assertFalse( str_contains( '', 'hello' ) );
	}
}
