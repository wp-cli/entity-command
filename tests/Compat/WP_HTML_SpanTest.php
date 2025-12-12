<?php
/**
 * Tests for WP_HTML_Span polyfill.
 *
 * @package WP_CLI\Entity\Compat
 */

namespace WP_CLI\Entity\Tests\Compat;

use PHPUnit\Framework\TestCase;
use WP_HTML_Span;

/**
 * Test the WP_HTML_Span polyfill class.
 */
class WP_HTML_SpanTest extends TestCase {

	/**
	 * Test constructor sets properties correctly.
	 */
	public function test_constructor_sets_start_and_length() {
		$span = new WP_HTML_Span( 10, 25 );

		$this->assertSame( 10, $span->start );
		$this->assertSame( 25, $span->length );
	}

	/**
	 * Test constructor with zero values.
	 */
	public function test_constructor_with_zero_values() {
		$span = new WP_HTML_Span( 0, 0 );

		$this->assertSame( 0, $span->start );
		$this->assertSame( 0, $span->length );
	}

	/**
	 * Test constructor with large values.
	 */
	public function test_constructor_with_large_values() {
		$span = new WP_HTML_Span( 1000000, 5000000 );

		$this->assertSame( 1000000, $span->start );
		$this->assertSame( 5000000, $span->length );
	}

	/**
	 * Test properties are public and accessible.
	 */
	public function test_properties_are_public() {
		$span = new WP_HTML_Span( 5, 10 );

		// Properties should be directly accessible.
		$span->start  = 20;
		$span->length = 30;

		$this->assertSame( 20, $span->start );
		$this->assertSame( 30, $span->length );
	}

	/**
	 * Test use case: extracting substring from document.
	 */
	public function test_use_case_extract_substring() {
		$document = '<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->';
		$span     = new WP_HTML_Span( 21, 18 ); // "<p>Hello World</p>"

		$extracted = substr( $document, $span->start, $span->length );

		$this->assertSame( '<p>Hello World</p>', $extracted );
	}
}
