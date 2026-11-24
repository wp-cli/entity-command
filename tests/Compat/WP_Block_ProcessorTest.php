<?php
/**
 * Tests for WP_Block_Processor polyfill.
 *
 * @package WP_CLI\Entity\Compat
 */

namespace WP_CLI\Entity\Tests\Compat;

use PHPUnit\Framework\TestCase;
use WP_Block_Processor;
use WP_HTML_Span;

/**
 * Test the WP_Block_Processor polyfill class.
 *
 * These tests verify that the polyfill behaves identically to the native
 * WordPress implementation.
 */
class WP_Block_ProcessorTest extends TestCase {

	/**
	 * Test next_block finds a simple paragraph block.
	 */
	public function test_next_block_finds_paragraph() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$this->assertTrue( $processor->next_block() );
		$this->assertSame( 'core/paragraph', $processor->get_block_type() );
	}

	/**
	 * Test next_block finds void blocks.
	 */
	public function test_next_block_finds_void_block() {
		$content   = '<!-- wp:spacer {"height":"50px"} /-->';
		$processor = new WP_Block_Processor( $content );

		$this->assertTrue( $processor->next_block() );
		$this->assertSame( 'core/spacer', $processor->get_block_type() );
		$this->assertSame( WP_Block_Processor::VOID, $processor->get_delimiter_type() );
	}

	/**
	 * Test next_block with specific block type filter.
	 */
	public function test_next_block_with_type_filter() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->';
		$processor = new WP_Block_Processor( $content );

		$this->assertTrue( $processor->next_block( 'heading' ) );
		$this->assertSame( 'core/heading', $processor->get_block_type() );
	}

	/**
	 * Test next_block returns false when no blocks found.
	 */
	public function test_next_block_returns_false_for_no_blocks() {
		$content   = '<p>Just regular HTML</p>';
		$processor = new WP_Block_Processor( $content );

		$this->assertFalse( $processor->next_block() );
	}

	/**
	 * Test next_block skips closers.
	 */
	public function test_next_block_skips_closers() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Title</h2><!-- /wp:heading -->';
		$processor = new WP_Block_Processor( $content );

		// First block is paragraph.
		$this->assertTrue( $processor->next_block() );
		$this->assertSame( 'core/paragraph', $processor->get_block_type() );

		// Second block is heading (skips the paragraph closer).
		$this->assertTrue( $processor->next_block() );
		$this->assertSame( 'core/heading', $processor->get_block_type() );

		// No more blocks.
		$this->assertFalse( $processor->next_block() );
	}

	/**
	 * Test get_block_type returns null for freeform content.
	 */
	public function test_get_block_type_returns_null_for_freeform() {
		$content   = 'Just text';
		$processor = new WP_Block_Processor( $content );

		$processor->next_token();

		$this->assertNull( $processor->get_block_type() );
		$this->assertTrue( $processor->is_html() );
	}

	/**
	 * Test get_block_type normalizes implicit core namespace.
	 */
	public function test_get_block_type_normalizes_namespace() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();

		$this->assertSame( 'core/paragraph', $processor->get_block_type() );
	}

	/**
	 * Test get_block_type preserves custom namespace.
	 */
	public function test_get_block_type_preserves_custom_namespace() {
		$content   = '<!-- wp:my-plugin/custom-block /-->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();

		$this->assertSame( 'my-plugin/custom-block', $processor->get_block_type() );
	}

	/**
	 * Test extract_full_block_and_advance returns correct structure.
	 */
	public function test_extract_full_block_returns_correct_structure() {
		$content   = '<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();
		$block = $processor->extract_full_block_and_advance();

		$this->assertIsArray( $block );
		$this->assertSame( 'core/paragraph', $block['blockName'] );
		$this->assertIsArray( $block['attrs'] );
		$this->assertIsArray( $block['innerBlocks'] );
		$this->assertArrayHasKey( 'innerHTML', $block );
		$this->assertArrayHasKey( 'innerContent', $block );
		$this->assertSame( '<p>Hello World</p>', $block['innerHTML'] );
	}

	/**
	 * Test extract_full_block_and_advance handles nested blocks.
	 */
	public function test_extract_full_block_handles_nested_blocks() {
		$content   = '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();
		$block = $processor->extract_full_block_and_advance();

		$this->assertSame( 'core/group', $block['blockName'] );
		$this->assertCount( 1, $block['innerBlocks'] );
		$this->assertSame( 'core/paragraph', $block['innerBlocks'][0]['blockName'] );
	}

	/**
	 * Test allocate_and_return_parsed_attributes parses JSON correctly.
	 */
	public function test_allocate_and_return_parsed_attributes_parses_json() {
		$content   = '<!-- wp:heading {"level":2,"textAlign":"center"} --><h2>Title</h2><!-- /wp:heading -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();
		$attrs = $processor->allocate_and_return_parsed_attributes();

		$this->assertIsArray( $attrs );
		$this->assertSame( 2, $attrs['level'] );
		$this->assertSame( 'center', $attrs['textAlign'] );
	}

	/**
	 * Test allocate_and_return_parsed_attributes returns null for void blocks without attrs.
	 */
	public function test_allocate_and_return_parsed_attributes_returns_null_without_json() {
		$content   = '<!-- wp:separator /-->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();
		$attrs = $processor->allocate_and_return_parsed_attributes();

		$this->assertNull( $attrs );
	}

	/**
	 * Test get_span returns correct byte offsets.
	 */
	public function test_get_span_returns_correct_offsets() {
		$content   = 'Before<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();
		$span = $processor->get_span();

		$this->assertInstanceOf( WP_HTML_Span::class, $span );
		$this->assertSame( 6, $span->start ); // After "Before".
		$this->assertGreaterThan( 0, $span->length );
	}

	/**
	 * Test get_depth tracks nesting correctly.
	 */
	public function test_get_depth_tracks_nesting() {
		$content   = '<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->';
		$processor = new WP_Block_Processor( $content );

		// Before anything.
		$this->assertSame( 0, $processor->get_depth() );

		// After entering group.
		$processor->next_block();
		$this->assertSame( 1, $processor->get_depth() );

		// After entering paragraph.
		$processor->next_block();
		$this->assertSame( 2, $processor->get_depth() );
	}

	/**
	 * Test get_breadcrumbs returns open block hierarchy.
	 */
	public function test_get_breadcrumbs_returns_hierarchy() {
		$content   = '<!-- wp:group --><!-- wp:columns --><!-- wp:column --><!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- /wp:column --><!-- /wp:columns --><!-- /wp:group -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block(); // group
		$processor->next_block(); // columns
		$processor->next_block(); // column
		$processor->next_block(); // paragraph

		$breadcrumbs = $processor->get_breadcrumbs();

		$this->assertSame(
			array( 'core/group', 'core/columns', 'core/column', 'core/paragraph' ),
			$breadcrumbs
		);
	}

	/**
	 * Test is_block_type with wildcard.
	 */
	public function test_is_block_type_with_wildcard() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();

		$this->assertTrue( $processor->is_block_type( '*' ) );
	}

	/**
	 * Test is_block_type with shorthand name.
	 */
	public function test_is_block_type_with_shorthand() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();

		$this->assertTrue( $processor->is_block_type( 'paragraph' ) );
		$this->assertTrue( $processor->is_block_type( 'core/paragraph' ) );
	}

	/**
	 * Test opens_block detects block openers.
	 */
	public function test_opens_block_detects_openers() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_block();

		$this->assertTrue( $processor->opens_block() );
		$this->assertTrue( $processor->opens_block( 'paragraph' ) );
		$this->assertFalse( $processor->opens_block( 'heading' ) );
	}

	/**
	 * Test get_delimiter_type returns correct types.
	 */
	public function test_get_delimiter_type_returns_correct_types() {
		$content   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_delimiter();
		$this->assertSame( WP_Block_Processor::OPENER, $processor->get_delimiter_type() );

		$processor->next_delimiter();
		$this->assertSame( WP_Block_Processor::CLOSER, $processor->get_delimiter_type() );
	}

	/**
	 * Test normalize_block_type static method.
	 */
	public function test_normalize_block_type() {
		$this->assertSame( 'core/paragraph', WP_Block_Processor::normalize_block_type( 'paragraph' ) );
		$this->assertSame( 'core/paragraph', WP_Block_Processor::normalize_block_type( 'core/paragraph' ) );
		$this->assertSame( 'my/block', WP_Block_Processor::normalize_block_type( 'my/block' ) );
	}

	/**
	 * Test empty content handling.
	 */
	public function test_empty_content_handling() {
		$processor = new WP_Block_Processor( '' );

		$this->assertFalse( $processor->next_block() );
		$this->assertFalse( $processor->next_token() );
	}

	/**
	 * Test multiple blocks iteration.
	 */
	public function test_multiple_blocks_iteration() {
		$content   = '<!-- wp:paragraph --><p>One</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Two</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Three</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$count = 0;
		while ( $processor->next_block() ) {
			++$count;
		}

		$this->assertSame( 3, $count );
	}

	/**
	 * Test deeply nested blocks.
	 */
	public function test_deeply_nested_blocks() {
		$content   = '<!-- wp:group --><!-- wp:group --><!-- wp:group --><!-- wp:paragraph --><p>Deep</p><!-- /wp:paragraph --><!-- /wp:group --><!-- /wp:group --><!-- /wp:group -->';
		$processor = new WP_Block_Processor( $content );

		// Navigate to the deepest block.
		$processor->next_block(); // First group.
		$processor->next_block(); // Second group.
		$processor->next_block(); // Third group.
		$processor->next_block(); // Paragraph.

		$this->assertSame( 4, $processor->get_depth() );
		$this->assertSame( 'core/paragraph', $processor->get_block_type() );
	}

	/**
	 * Test freeform HTML content detection.
	 */
	public function test_freeform_html_content() {
		$content   = '<p>Freeform HTML</p><!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		// First token should be HTML.
		$processor->next_token();
		$this->assertTrue( $processor->is_html() );

		// Second token should be the paragraph opener.
		$processor->next_token();
		$this->assertFalse( $processor->is_html() );
		$this->assertSame( 'core/paragraph', $processor->get_block_type() );
	}

	/**
	 * Test is_non_whitespace_html distinguishes content.
	 */
	public function test_is_non_whitespace_html() {
		$content   = "\n\n<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->\n\n";
		$processor = new WP_Block_Processor( $content );

		// First token is whitespace HTML.
		$processor->next_token();
		$this->assertTrue( $processor->is_html() );
		$this->assertFalse( $processor->is_non_whitespace_html() );
	}

	/**
	 * Test get_html_content returns correct content.
	 */
	public function test_get_html_content() {
		$content   = '<p>Freeform</p><!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->';
		$processor = new WP_Block_Processor( $content );

		$processor->next_token();
		$this->assertSame( '<p>Freeform</p>', $processor->get_html_content() );
	}

	// =========================================================================
	// Additional Polyfill Verification Tests (Task 9)
	// =========================================================================

	/**
	 * Test next_block with type parameter filters correctly.
	 */
	public function test_next_block_type_filter_with_namespace() {
		$content  = '<!-- wp:paragraph --><p>Skip</p><!-- /wp:paragraph -->';
		$content .= '<!-- wp:heading --><h2>Find</h2><!-- /wp:heading -->';

		$processor = new WP_Block_Processor( $content );

		// Should skip paragraph and find heading.
		$this->assertTrue( $processor->next_block( 'core/heading' ) );
		$this->assertSame( 'core/heading', $processor->get_block_type() );
	}

	/**
	 * Test next_block with shorthand type name.
	 */
	public function test_next_block_type_filter_shorthand() {
		$content  = '<!-- wp:paragraph --><p>Skip</p><!-- /wp:paragraph -->';
		$content .= '<!-- wp:heading --><h2>Find</h2><!-- /wp:heading -->';

		$processor = new WP_Block_Processor( $content );

		// Shorthand should also work.
		$this->assertTrue( $processor->next_block( 'heading' ) );
		$this->assertSame( 'core/heading', $processor->get_block_type() );
	}

	/**
	 * Test get_breadcrumbs returns correct path.
	 */
	public function test_get_breadcrumbs_nested() {
		$content = '<!-- wp:group --><!-- wp:columns --><!-- wp:column --><!-- wp:paragraph --><p>Deep</p><!-- /wp:paragraph --><!-- /wp:column --><!-- /wp:columns --><!-- /wp:group -->';

		$processor = new WP_Block_Processor( $content );

		// Navigate to paragraph.
		while ( $processor->next_block() ) {
			if ( 'core/paragraph' === $processor->get_block_type() ) {
				break;
			}
		}

		$breadcrumbs = $processor->get_breadcrumbs();

		$this->assertContains( 'core/group', $breadcrumbs );
		$this->assertContains( 'core/columns', $breadcrumbs );
		$this->assertContains( 'core/column', $breadcrumbs );
	}

	/**
	 * Test is_block_type with custom namespace.
	 */
	public function test_is_block_type_custom_namespace() {
		$content   = '<!-- wp:my-plugin/custom-block /-->';
		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$this->assertTrue( $processor->is_block_type( 'my-plugin/custom-block' ) );
		$this->assertFalse( $processor->is_block_type( 'other-plugin/custom-block' ) );
		$this->assertFalse( $processor->is_block_type( 'my-plugin/other-block' ) );
	}

	/**
	 * Test extract_full_block_and_advance returns correct structure.
	 */
	public function test_extract_full_block_structure() {
		$content = '<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">Test</p><!-- /wp:paragraph -->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$block = $processor->extract_full_block_and_advance();

		$this->assertArrayHasKey( 'blockName', $block );
		$this->assertArrayHasKey( 'attrs', $block );
		$this->assertArrayHasKey( 'innerBlocks', $block );
		$this->assertArrayHasKey( 'innerHTML', $block );
		$this->assertArrayHasKey( 'innerContent', $block );

		$this->assertSame( 'core/paragraph', $block['blockName'] );
		$this->assertSame( 'center', $block['attrs']['align'] );
		$this->assertEmpty( $block['innerBlocks'] );
		$this->assertStringContainsString( 'Test', $block['innerHTML'] );
	}

	/**
	 * Test extract_full_block_and_advance with nested blocks.
	 */
	public function test_extract_full_block_with_nested() {
		$content = '<!-- wp:group --><div><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --></div><!-- /wp:group -->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$block = $processor->extract_full_block_and_advance();

		$this->assertSame( 'core/group', $block['blockName'] );
		$this->assertCount( 1, $block['innerBlocks'] );
		$this->assertSame( 'core/paragraph', $block['innerBlocks'][0]['blockName'] );
	}

	/**
	 * Test handling of complex nested attributes.
	 */
	public function test_complex_nested_attributes() {
		$content = '<!-- wp:gallery {"ids":[1,2,3],"nested":{"deep":{"value":true}}} /-->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$attrs = $processor->allocate_and_return_parsed_attributes();

		$this->assertSame( [ 1, 2, 3 ], $attrs['ids'] );
		$this->assertSame( true, $attrs['nested']['deep']['value'] );
	}

	/**
	 * Test that opener detection works correctly.
	 */
	public function test_opens_block_with_specific_type() {
		$content = '<!-- wp:heading {"level":2} --><h2>Title</h2><!-- /wp:heading -->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$this->assertTrue( $processor->opens_block() );
		$this->assertTrue( $processor->opens_block( 'heading' ) );
		$this->assertTrue( $processor->opens_block( 'core/heading' ) );
		$this->assertFalse( $processor->opens_block( 'paragraph' ) );
	}

	/**
	 * Test multiple sequential blocks with extraction.
	 */
	public function test_multiple_blocks_extraction() {
		$content  = '<!-- wp:paragraph --><p>One</p><!-- /wp:paragraph -->';
		$content .= '<!-- wp:heading --><h2>Two</h2><!-- /wp:heading -->';
		$content .= '<!-- wp:paragraph --><p>Three</p><!-- /wp:paragraph -->';

		$processor = new WP_Block_Processor( $content );
		$blocks    = [];

		while ( $processor->next_block() ) {
			$blocks[] = $processor->extract_full_block_and_advance();
		}

		$this->assertCount( 3, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
		$this->assertSame( 'core/heading', $blocks[1]['blockName'] );
		$this->assertSame( 'core/paragraph', $blocks[2]['blockName'] );
	}

	/**
	 * Test depth tracking with multiple nested levels.
	 */
	public function test_depth_tracking_complex() {
		$content = '<!-- wp:group --><!-- wp:columns --><!-- wp:column --><!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph --><!-- /wp:column --><!-- /wp:columns --><!-- /wp:group -->';

		$processor = new WP_Block_Processor( $content );
		$max_depth = 0;

		while ( $processor->next_block() ) {
			$depth = $processor->get_depth();
			if ( $depth > $max_depth ) {
				$max_depth = $depth;
			}
		}

		$this->assertSame( 4, $max_depth );
	}

	/**
	 * Test void block with complex attributes.
	 */
	public function test_void_block_with_complex_attrs() {
		$content = '<!-- wp:image {"id":123,"sizeSlug":"large","linkDestination":"media","className":"is-style-rounded"} /-->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$this->assertSame( WP_Block_Processor::VOID, $processor->get_delimiter_type() );

		$attrs = $processor->allocate_and_return_parsed_attributes();

		$this->assertSame( 123, $attrs['id'] );
		$this->assertSame( 'large', $attrs['sizeSlug'] );
		$this->assertSame( 'media', $attrs['linkDestination'] );
		$this->assertSame( 'is-style-rounded', $attrs['className'] );
	}

	/**
	 * Test handling of Unicode in block content.
	 */
	public function test_unicode_content_handling() {
		$content = '<!-- wp:paragraph {"text":"日本語テスト"} --><p>日本語テスト</p><!-- /wp:paragraph -->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$attrs = $processor->allocate_and_return_parsed_attributes();

		$this->assertSame( '日本語テスト', $attrs['text'] );
	}

	/**
	 * Test span offsets for block in middle of content.
	 */
	public function test_span_offset_middle_block() {
		$prefix  = 'Some text before ';
		$block   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$suffix  = ' some text after';
		$content = $prefix . $block . $suffix;

		$processor = new WP_Block_Processor( $content );
		$processor->next_block();

		$span = $processor->get_span();

		$this->assertSame( strlen( $prefix ), $span->start );
	}

	/**
	 * Test is_non_whitespace_html with actual content.
	 */
	public function test_is_non_whitespace_html_with_content() {
		$content = '<p>Real content</p><!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->';

		$processor = new WP_Block_Processor( $content );
		$processor->next_token();

		$this->assertTrue( $processor->is_html() );
		$this->assertTrue( $processor->is_non_whitespace_html() );
	}
}
