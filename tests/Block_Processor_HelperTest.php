<?php
/**
 * Tests for Block_Processor_Helper class.
 *
 * @package WP_CLI\Entity
 */

namespace WP_CLI\Entity\Tests;

use PHPUnit\Framework\TestCase;
use WP_CLI\Entity\Block_Processor_Helper;

/**
 * Test the Block_Processor_Helper class.
 *
 * These tests verify the helper methods work correctly with the
 * WP_Block_Processor polyfill.
 */
class Block_Processor_HelperTest extends TestCase {

	/**
	 * Sample block content for testing.
	 *
	 * @var string
	 */
	private $sample_content;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Create sample content with multiple blocks.
		$this->sample_content = implode(
			'',
			[
				'<!-- wp:paragraph --><p>First paragraph</p><!-- /wp:paragraph -->',
				'<!-- wp:heading {"level":2} --><h2>My Heading</h2><!-- /wp:heading -->',
				'<!-- wp:paragraph --><p>Second paragraph</p><!-- /wp:paragraph -->',
				'<!-- wp:image {"id":123} /-->',
			]
		);
	}

	// =========================================================================
	// Tests for parse_all()
	// =========================================================================

	/**
	 * Test parse_all returns correct structure for simple content.
	 */
	public function test_parse_all_returns_correct_structure() {
		$content = '<!-- wp:paragraph --><p>Hello</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
		$this->assertArrayHasKey( 'attrs', $blocks[0] );
		$this->assertArrayHasKey( 'innerBlocks', $blocks[0] );
		$this->assertArrayHasKey( 'innerHTML', $blocks[0] );
		$this->assertArrayHasKey( 'innerContent', $blocks[0] );
	}

	/**
	 * Test parse_all handles multiple blocks.
	 */
	public function test_parse_all_handles_multiple_blocks() {
		$blocks = Block_Processor_Helper::parse_all( $this->sample_content );

		$this->assertCount( 4, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
		$this->assertSame( 'core/heading', $blocks[1]['blockName'] );
		$this->assertSame( 'core/paragraph', $blocks[2]['blockName'] );
		$this->assertSame( 'core/image', $blocks[3]['blockName'] );
	}

	/**
	 * Test parse_all handles nested blocks.
	 */
	public function test_parse_all_handles_nested_blocks() {
		$content = '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/group', $blocks[0]['blockName'] );
		$this->assertCount( 1, $blocks[0]['innerBlocks'] );
		$this->assertSame( 'core/paragraph', $blocks[0]['innerBlocks'][0]['blockName'] );
	}

	/**
	 * Test parse_all handles void blocks.
	 */
	public function test_parse_all_handles_void_blocks() {
		$content = '<!-- wp:separator /--><!-- wp:spacer {"height":"50px"} /-->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 2, $blocks );
		$this->assertSame( 'core/separator', $blocks[0]['blockName'] );
		$this->assertSame( 'core/spacer', $blocks[1]['blockName'] );
	}

	/**
	 * Test parse_all returns empty array for empty content.
	 */
	public function test_parse_all_returns_empty_for_empty_content() {
		$blocks = Block_Processor_Helper::parse_all( '' );

		$this->assertSame( [], $blocks );
	}

	/**
	 * Test parse_all parses attributes correctly.
	 */
	public function test_parse_all_parses_attributes() {
		$content = '<!-- wp:heading {"level":3,"textAlign":"center"} --><h3>Title</h3><!-- /wp:heading -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 3, $blocks[0]['attrs']['level'] );
		$this->assertSame( 'center', $blocks[0]['attrs']['textAlign'] );
	}

	// =========================================================================
	// Tests for get_at_index()
	// =========================================================================

	/**
	 * Test get_at_index returns correct block.
	 */
	public function test_get_at_index_returns_correct_block() {
		$block = Block_Processor_Helper::get_at_index( $this->sample_content, 1 );

		$this->assertNotNull( $block );
		$this->assertSame( 'core/heading', $block['blockName'] );
	}

	/**
	 * Test get_at_index returns first block at index 0.
	 */
	public function test_get_at_index_returns_first_block() {
		$block = Block_Processor_Helper::get_at_index( $this->sample_content, 0 );

		$this->assertNotNull( $block );
		$this->assertSame( 'core/paragraph', $block['blockName'] );
	}

	/**
	 * Test get_at_index returns last block.
	 */
	public function test_get_at_index_returns_last_block() {
		$block = Block_Processor_Helper::get_at_index( $this->sample_content, 3 );

		$this->assertNotNull( $block );
		$this->assertSame( 'core/image', $block['blockName'] );
	}

	/**
	 * Test get_at_index returns null for invalid index.
	 */
	public function test_get_at_index_returns_null_for_invalid_index() {
		$block = Block_Processor_Helper::get_at_index( $this->sample_content, 10 );

		$this->assertNull( $block );
	}

	/**
	 * Test get_at_index returns null for negative index.
	 */
	public function test_get_at_index_returns_null_for_negative_index() {
		$block = Block_Processor_Helper::get_at_index( $this->sample_content, -1 );

		$this->assertNull( $block );
	}

	/**
	 * Test get_at_index returns null for empty content.
	 */
	public function test_get_at_index_returns_null_for_empty_content() {
		$block = Block_Processor_Helper::get_at_index( '', 0 );

		$this->assertNull( $block );
	}

	// =========================================================================
	// Tests for count_by_type()
	// =========================================================================

	/**
	 * Test count_by_type returns correct counts.
	 */
	public function test_count_by_type_returns_correct_counts() {
		$counts = Block_Processor_Helper::count_by_type( $this->sample_content );

		$this->assertSame( 2, $counts['core/paragraph'] );
		$this->assertSame( 1, $counts['core/heading'] );
		$this->assertSame( 1, $counts['core/image'] );
	}

	/**
	 * Test count_by_type with nested blocks excluded by default.
	 */
	public function test_count_by_type_excludes_nested_by_default() {
		$content = '<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->';
		$counts  = Block_Processor_Helper::count_by_type( $content );

		$this->assertSame( 1, $counts['core/group'] );
		$this->assertArrayNotHasKey( 'core/paragraph', $counts );
	}

	/**
	 * Test count_by_type with nested blocks included.
	 */
	public function test_count_by_type_includes_nested_when_requested() {
		$content = '<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->';
		$counts  = Block_Processor_Helper::count_by_type( $content, true );

		$this->assertSame( 1, $counts['core/group'] );
		$this->assertSame( 1, $counts['core/paragraph'] );
	}

	/**
	 * Test count_by_type returns empty array for empty content.
	 */
	public function test_count_by_type_returns_empty_for_empty_content() {
		$counts = Block_Processor_Helper::count_by_type( '' );

		$this->assertSame( [], $counts );
	}

	/**
	 * Test count_by_type returns empty array for plain HTML.
	 */
	public function test_count_by_type_returns_empty_for_plain_html() {
		$counts = Block_Processor_Helper::count_by_type( '<p>Just HTML</p>' );

		$this->assertSame( [], $counts );
	}

	// =========================================================================
	// Tests for has_block()
	// =========================================================================

	/**
	 * Test has_block returns true when block exists.
	 */
	public function test_has_block_returns_true_when_exists() {
		$this->assertTrue( Block_Processor_Helper::has_block( $this->sample_content, 'core/heading' ) );
	}

	/**
	 * Test has_block works with shorthand block names.
	 */
	public function test_has_block_works_with_shorthand() {
		$this->assertTrue( Block_Processor_Helper::has_block( $this->sample_content, 'heading' ) );
		$this->assertTrue( Block_Processor_Helper::has_block( $this->sample_content, 'paragraph' ) );
	}

	/**
	 * Test has_block returns false when block doesn't exist.
	 */
	public function test_has_block_returns_false_when_missing() {
		$this->assertFalse( Block_Processor_Helper::has_block( $this->sample_content, 'core/quote' ) );
	}

	/**
	 * Test has_block returns false for empty content.
	 */
	public function test_has_block_returns_false_for_empty_content() {
		$this->assertFalse( Block_Processor_Helper::has_block( '', 'paragraph' ) );
	}

	/**
	 * Test has_block finds nested blocks.
	 */
	public function test_has_block_finds_nested_blocks() {
		$content = '<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->';

		$this->assertTrue( Block_Processor_Helper::has_block( $content, 'paragraph' ) );
	}

	// =========================================================================
	// Tests for get_block_count()
	// =========================================================================

	/**
	 * Test get_block_count returns correct count.
	 */
	public function test_get_block_count_returns_correct_count() {
		$count = Block_Processor_Helper::get_block_count( $this->sample_content );

		$this->assertSame( 4, $count );
	}

	/**
	 * Test get_block_count excludes nested by default.
	 */
	public function test_get_block_count_excludes_nested_by_default() {
		$content = '<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->';
		$count   = Block_Processor_Helper::get_block_count( $content );

		$this->assertSame( 1, $count );
	}

	/**
	 * Test get_block_count includes nested when requested.
	 */
	public function test_get_block_count_includes_nested_when_requested() {
		$content = '<!-- wp:group --><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --><!-- /wp:group -->';
		$count   = Block_Processor_Helper::get_block_count( $content, true );

		$this->assertSame( 2, $count );
	}

	/**
	 * Test get_block_count returns 0 for empty content.
	 */
	public function test_get_block_count_returns_zero_for_empty() {
		$count = Block_Processor_Helper::get_block_count( '' );

		$this->assertSame( 0, $count );
	}

	// =========================================================================
	// Tests for has_blocks()
	// =========================================================================

	/**
	 * Test has_blocks returns true when blocks exist.
	 */
	public function test_has_blocks_returns_true_when_exists() {
		$this->assertTrue( Block_Processor_Helper::has_blocks( $this->sample_content ) );
	}

	/**
	 * Test has_blocks returns false for empty content.
	 */
	public function test_has_blocks_returns_false_for_empty() {
		$this->assertFalse( Block_Processor_Helper::has_blocks( '' ) );
	}

	/**
	 * Test has_blocks returns false for plain HTML.
	 */
	public function test_has_blocks_returns_false_for_plain_html() {
		$this->assertFalse( Block_Processor_Helper::has_blocks( '<p>Just HTML</p>' ) );
	}

	// =========================================================================
	// Tests for get_block_types()
	// =========================================================================

	/**
	 * Test get_block_types returns unique types.
	 */
	public function test_get_block_types_returns_unique_types() {
		$types = Block_Processor_Helper::get_block_types( $this->sample_content );

		$this->assertContains( 'core/paragraph', $types );
		$this->assertContains( 'core/heading', $types );
		$this->assertContains( 'core/image', $types );
		$this->assertCount( 3, $types );
	}

	/**
	 * Test get_block_types returns empty array for empty content.
	 */
	public function test_get_block_types_returns_empty_for_empty() {
		$types = Block_Processor_Helper::get_block_types( '' );

		$this->assertSame( [], $types );
	}

	// =========================================================================
	// Tests for extract_matching()
	// =========================================================================

	/**
	 * Test extract_matching finds blocks by type.
	 */
	public function test_extract_matching_finds_blocks_by_type() {
		$blocks = Block_Processor_Helper::extract_matching(
			$this->sample_content,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback signature requires both params.
			function ( $type, $attrs ) {
				return 'core/paragraph' === $type;
			}
		);

		$this->assertCount( 2, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
		$this->assertSame( 'core/paragraph', $blocks[1]['blockName'] );
	}

	/**
	 * Test extract_matching finds blocks by attribute.
	 */
	public function test_extract_matching_finds_blocks_by_attribute() {
		$blocks = Block_Processor_Helper::extract_matching(
			$this->sample_content,
			function ( $type, $attrs ) {
				return isset( $attrs['level'] ) && 2 === $attrs['level'];
			}
		);

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/heading', $blocks[0]['blockName'] );
	}

	/**
	 * Test extract_matching respects limit.
	 */
	public function test_extract_matching_respects_limit() {
		$blocks = Block_Processor_Helper::extract_matching(
			$this->sample_content,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback signature requires both params.
			function ( $type, $attrs ) {
				return 'core/paragraph' === $type;
			},
			1
		);

		$this->assertCount( 1, $blocks );
	}

	/**
	 * Test extract_matching returns empty for no matches.
	 */
	public function test_extract_matching_returns_empty_for_no_matches() {
		$blocks = Block_Processor_Helper::extract_matching(
			$this->sample_content,
			// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Callback signature requires both params.
			function ( $type, $attrs ) {
				return 'core/quote' === $type;
			}
		);

		$this->assertSame( [], $blocks );
	}

	// =========================================================================
	// Tests for get_block_span()
	// =========================================================================

	/**
	 * Test get_block_span returns correct offsets.
	 */
	public function test_get_block_span_returns_correct_offsets() {
		$content = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$span    = Block_Processor_Helper::get_block_span( $content, 0 );

		$this->assertNotNull( $span );
		$this->assertArrayHasKey( 'start', $span );
		$this->assertArrayHasKey( 'end', $span );
		$this->assertSame( 0, $span['start'] );
		$this->assertSame( strlen( $content ), $span['end'] );
	}

	/**
	 * Test get_block_span returns null for invalid index.
	 */
	public function test_get_block_span_returns_null_for_invalid_index() {
		$span = Block_Processor_Helper::get_block_span( $this->sample_content, 100 );

		$this->assertNull( $span );
	}

	/**
	 * Test get_block_span returns null for empty content.
	 */
	public function test_get_block_span_returns_null_for_empty() {
		$span = Block_Processor_Helper::get_block_span( '', 0 );

		$this->assertNull( $span );
	}

	// =========================================================================
	// Tests for strip_inner_html()
	// =========================================================================

	/**
	 * Test strip_inner_html removes innerHTML.
	 */
	public function test_strip_inner_html_removes_content() {
		$blocks = [
			[
				'blockName'    => 'core/paragraph',
				'attrs'        => [],
				'innerBlocks'  => [],
				'innerHTML'    => '<p>Test</p>',
				'innerContent' => [ '<p>Test</p>' ],
			],
		];

		$stripped = Block_Processor_Helper::strip_inner_html( $blocks );

		$this->assertArrayNotHasKey( 'innerHTML', $stripped[0] );
		$this->assertArrayNotHasKey( 'innerContent', $stripped[0] );
		$this->assertSame( 'core/paragraph', $stripped[0]['blockName'] );
	}

	/**
	 * Test strip_inner_html works recursively on nested blocks.
	 */
	public function test_strip_inner_html_works_recursively() {
		$blocks = [
			[
				'blockName'    => 'core/group',
				'attrs'        => [],
				'innerHTML'    => '<div></div>',
				'innerContent' => [ '<div>', '</div>' ],
				'innerBlocks'  => [
					[
						'blockName'    => 'core/paragraph',
						'attrs'        => [],
						'innerHTML'    => '<p>Inner</p>',
						'innerContent' => [ '<p>Inner</p>' ],
						'innerBlocks'  => [],
					],
				],
			],
		];

		$stripped = Block_Processor_Helper::strip_inner_html( $blocks );

		$this->assertArrayNotHasKey( 'innerHTML', $stripped[0] );
		$this->assertArrayNotHasKey( 'innerHTML', $stripped[0]['innerBlocks'][0] );
	}

	// =========================================================================
	// Tests for filter_empty_blocks()
	// =========================================================================

	/**
	 * Test filter_empty_blocks removes null blockName entries.
	 */
	public function test_filter_empty_blocks_removes_empty() {
		$blocks = [
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [],
			],
			[
				'blockName' => null,
				'attrs'     => [],
			],
			[
				'blockName' => 'core/heading',
				'attrs'     => [],
			],
			[
				'blockName' => '',
				'attrs'     => [],
			],
		];

		$filtered = Block_Processor_Helper::filter_empty_blocks( $blocks );

		$this->assertCount( 2, $filtered );
		$this->assertSame( 'core/paragraph', $filtered[0]['blockName'] );
		$this->assertSame( 'core/heading', $filtered[1]['blockName'] );
	}

	/**
	 * Test filter_empty_blocks re-indexes array.
	 */
	public function test_filter_empty_blocks_reindexes() {
		$blocks = [
			0 => [ 'blockName' => null ],
			1 => [
				'blockName' => 'core/paragraph',
				'attrs'     => [],
			],
		];

		$filtered = Block_Processor_Helper::filter_empty_blocks( $blocks );

		$this->assertArrayHasKey( 0, $filtered );
		$this->assertArrayNotHasKey( 1, $filtered );
	}

	// =========================================================================
	// Edge case tests
	// =========================================================================

	/**
	 * Test handling of deeply nested blocks.
	 */
	public function test_deeply_nested_blocks() {
		$content = '<!-- wp:group --><!-- wp:group --><!-- wp:group --><!-- wp:paragraph --><p>Deep</p><!-- /wp:paragraph --><!-- /wp:group --><!-- /wp:group --><!-- /wp:group -->';

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/group', $blocks[0]['blockName'] );

		// Navigate to deepest block.
		$inner = $blocks[0]['innerBlocks'][0]['innerBlocks'][0]['innerBlocks'][0];
		$this->assertSame( 'core/paragraph', $inner['blockName'] );
	}

	/**
	 * Test handling of blocks with complex JSON attributes.
	 */
	public function test_complex_json_attributes() {
		$content = '<!-- wp:gallery {"ids":[1,2,3],"columns":3,"linkTo":"media","nested":{"key":"value"}} /-->';

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( [ 1, 2, 3 ], $blocks[0]['attrs']['ids'] );
		$this->assertSame( 3, $blocks[0]['attrs']['columns'] );
		$this->assertSame( 'media', $blocks[0]['attrs']['linkTo'] );
		$this->assertSame( [ 'key' => 'value' ], $blocks[0]['attrs']['nested'] );
	}

	/**
	 * Test handling of custom namespaced blocks.
	 */
	public function test_custom_namespace_blocks() {
		$content = '<!-- wp:my-plugin/custom-block {"option":"value"} /-->';

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'my-plugin/custom-block', $blocks[0]['blockName'] );
		$this->assertSame( 'value', $blocks[0]['attrs']['option'] );
	}

	// =========================================================================
	// Serialization Round-Trip Tests (Task 1)
	// =========================================================================

	/**
	 * Normalize block structure for comparison (remove keys that may differ).
	 *
	 * @param array $blocks Blocks to normalize.
	 * @return array Normalized blocks.
	 */
	private function normalize_for_comparison( array $blocks ) {
		return array_map(
			function ( $block ) {
				return [
					'blockName'   => $block['blockName'],
					'attrs'       => $block['attrs'] ?? [],
					'innerBlocks' => isset( $block['innerBlocks'] )
						? $this->normalize_for_comparison( $block['innerBlocks'] )
						: [],
				];
			},
			$blocks
		);
	}

	/**
	 * Serialize an array of blocks to string (test helper).
	 *
	 * @param array $blocks Blocks to serialize.
	 * @return string Serialized content.
	 */
	private function serialize_blocks_for_test( array $blocks ) {
		$output = '';
		foreach ( $blocks as $block ) {
			$output .= $this->serialize_block_for_test( $block );
		}
		return $output;
	}

	/**
	 * Serialize a single block to string (test helper).
	 *
	 * @param array $block Block to serialize.
	 * @return string Serialized content.
	 */
	private function serialize_block_for_test( array $block ) {
		$block_name = $block['blockName'] ?? null;

		if ( empty( $block_name ) ) {
			return $block['innerHTML'] ?? '';
		}

		$name = $block_name;
		if ( 0 === strpos( $name, 'core/' ) ) {
			$name = substr( $name, 5 );
		}

		$attrs = '';
		if ( ! empty( $block['attrs'] ) ) {
			$attrs = ' ' . json_encode( $block['attrs'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		if ( empty( $block['innerContent'] ) ) {
			return "<!-- wp:{$name}{$attrs} /-->";
		}

		$output      = "<!-- wp:{$name}{$attrs} -->";
		$inner_index = 0;
		foreach ( $block['innerContent'] as $chunk ) {
			if ( null === $chunk ) {
				if ( isset( $block['innerBlocks'][ $inner_index ] ) ) {
					$output .= $this->serialize_block_for_test( $block['innerBlocks'][ $inner_index ] );
					++$inner_index;
				}
			} else {
				$output .= $chunk;
			}
		}
		$output .= "<!-- /wp:{$name} -->";

		return $output;
	}

	/**
	 * Test that parsed blocks can be serialized and re-parsed consistently.
	 */
	public function test_serialization_roundtrip_simple_paragraph() {
		$original   = '<!-- wp:paragraph --><p>Test</p><!-- /wp:paragraph -->';
		$parsed     = Block_Processor_Helper::parse_all( $original );
		$serialized = $this->serialize_blocks_for_test( $parsed );
		$reparsed   = Block_Processor_Helper::parse_all( $serialized );

		$this->assertEquals(
			$this->normalize_for_comparison( $parsed ),
			$this->normalize_for_comparison( $reparsed )
		);
	}

	/**
	 * Test serialization round-trip with attributes.
	 */
	public function test_serialization_roundtrip_with_attributes() {
		$original   = '<!-- wp:heading {"level":3,"textAlign":"center"} --><h3 class="has-text-align-center">Title</h3><!-- /wp:heading -->';
		$parsed     = Block_Processor_Helper::parse_all( $original );
		$serialized = $this->serialize_blocks_for_test( $parsed );
		$reparsed   = Block_Processor_Helper::parse_all( $serialized );

		$this->assertEquals(
			$this->normalize_for_comparison( $parsed ),
			$this->normalize_for_comparison( $reparsed )
		);
	}

	/**
	 * Test serialization round-trip with nested blocks.
	 */
	public function test_serialization_roundtrip_nested_blocks() {
		$original   = '<!-- wp:group {"className":"test"} --><div class="wp-block-group test"><!-- wp:paragraph --><p>Inner</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
		$parsed     = Block_Processor_Helper::parse_all( $original );
		$serialized = $this->serialize_blocks_for_test( $parsed );
		$reparsed   = Block_Processor_Helper::parse_all( $serialized );

		$this->assertEquals(
			$this->normalize_for_comparison( $parsed ),
			$this->normalize_for_comparison( $reparsed )
		);
	}

	/**
	 * Test serialization round-trip with void blocks.
	 */
	public function test_serialization_roundtrip_void_blocks() {
		$original   = '<!-- wp:separator {"className":"is-style-wide"} /-->';
		$parsed     = Block_Processor_Helper::parse_all( $original );
		$serialized = $this->serialize_blocks_for_test( $parsed );
		$reparsed   = Block_Processor_Helper::parse_all( $serialized );

		$this->assertEquals(
			$this->normalize_for_comparison( $parsed ),
			$this->normalize_for_comparison( $reparsed )
		);
	}

	/**
	 * Test serialization round-trip with multiple blocks.
	 */
	public function test_serialization_roundtrip_multiple_blocks() {
		$original   = '<!-- wp:paragraph --><p>One</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>Two</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Three</h2><!-- /wp:heading -->';
		$parsed     = Block_Processor_Helper::parse_all( $original );
		$serialized = $this->serialize_blocks_for_test( $parsed );
		$reparsed   = Block_Processor_Helper::parse_all( $serialized );

		$this->assertEquals(
			$this->normalize_for_comparison( $parsed ),
			$this->normalize_for_comparison( $reparsed )
		);
	}

	// =========================================================================
	// Malformed Content Handling Tests (Task 2)
	// =========================================================================

	/**
	 * Test handling of unclosed block (missing closer).
	 */
	public function test_parse_all_handles_unclosed_block() {
		$content = '<!-- wp:paragraph --><p>No closing tag';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		// Should not throw, may return empty or partial.
		$this->assertIsArray( $blocks );
	}

	/**
	 * Test handling of orphaned closer (no opener).
	 */
	public function test_parse_all_handles_orphaned_closer() {
		$content = '<p>Some text</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
	}

	/**
	 * Test handling of mismatched block types.
	 */
	public function test_parse_all_handles_mismatched_blocks() {
		$content = '<!-- wp:paragraph --><p>Text</p><!-- /wp:heading -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
	}

	/**
	 * Test handling of invalid JSON in attributes.
	 */
	public function test_parse_all_handles_invalid_json_attrs() {
		$content = '<!-- wp:paragraph {"broken: json} --><p>Test</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
	}

	/**
	 * Test handling of truncated block comment.
	 */
	public function test_parse_all_handles_truncated_comment() {
		$content = '<!-- wp:paragr';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
		$this->assertCount( 0, $blocks );
	}

	/**
	 * Test handling of empty block name.
	 */
	public function test_parse_all_handles_empty_block_name() {
		$content = '<!-- wp: --><p>Test</p><!-- /wp: -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
	}

	/**
	 * Test handling of block with only whitespace content.
	 */
	public function test_parse_all_handles_whitespace_only_content() {
		$content = "<!-- wp:paragraph -->   \n\n   <!-- /wp:paragraph -->";
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
	}

	/**
	 * Test handling of deeply nested unclosed blocks.
	 */
	public function test_parse_all_handles_nested_unclosed() {
		$content = '<!-- wp:group --><!-- wp:paragraph --><p>Unclosed nesting';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
	}

	// =========================================================================
	// Unicode and Special Character Tests (Task 3)
	// =========================================================================

	/**
	 * Test handling of emoji in attributes.
	 */
	public function test_parse_all_handles_emoji_in_attrs() {
		$content = '<!-- wp:paragraph {"emoji":"🎉🚀💻"} --><p>Test</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( '🎉🚀💻', $blocks[0]['attrs']['emoji'] );
	}

	/**
	 * Test handling of Chinese characters in attributes.
	 */
	public function test_parse_all_handles_chinese_chars() {
		$content = '<!-- wp:paragraph {"text":"中文测试"} --><p>中文测试</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( '中文测试', $blocks[0]['attrs']['text'] );
	}

	/**
	 * Test handling of RTL characters (Arabic/Hebrew).
	 */
	public function test_parse_all_handles_rtl_chars() {
		$content = '<!-- wp:paragraph {"text":"مرحبا بالعالم"} --><p>مرحبا بالعالم</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'مرحبا بالعالم', $blocks[0]['attrs']['text'] );
	}

	/**
	 * Test handling of escaped quotes in attributes.
	 */
	public function test_parse_all_handles_escaped_quotes() {
		$content = '<!-- wp:paragraph {"text":"He said \"hello\""} --><p>Test</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'He said "hello"', $blocks[0]['attrs']['text'] );
	}

	/**
	 * Test handling of newlines in attributes.
	 */
	public function test_parse_all_handles_newlines_in_attrs() {
		$content = '<!-- wp:paragraph {"text":"Line1\\nLine2"} --><p>Test</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertStringContainsString( "\n", $blocks[0]['attrs']['text'] );
	}

	/**
	 * Test handling of HTML entities in content.
	 */
	public function test_parse_all_handles_html_entities() {
		$content = '<!-- wp:paragraph --><p>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertStringContainsString( '&lt;script&gt;', $blocks[0]['innerHTML'] );
	}

	/**
	 * Test handling of null bytes (should be stripped or handled).
	 */
	public function test_parse_all_handles_null_bytes() {
		$content = "<!-- wp:paragraph --><p>Test\x00with\x00nulls</p><!-- /wp:paragraph -->";
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertIsArray( $blocks );
	}

	// =========================================================================
	// Deep Nesting Tests (Task 4)
	// =========================================================================

	/**
	 * Test handling of 10-level deep nesting.
	 */
	public function test_parse_all_handles_10_level_nesting() {
		$depth    = 10;
		$content  = str_repeat( '<!-- wp:group --><div class="wp-block-group">', $depth );
		$content .= '<!-- wp:paragraph --><p>Deep content</p><!-- /wp:paragraph -->';
		$content .= str_repeat( '</div><!-- /wp:group -->', $depth );

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/group', $blocks[0]['blockName'] );

		// Navigate to deepest block.
		$current = $blocks[0];
		for ( $i = 1; $i < $depth; $i++ ) {
			$this->assertNotEmpty( $current['innerBlocks'] );
			$current = $current['innerBlocks'][0];
		}
		$this->assertSame( 'core/paragraph', $current['innerBlocks'][0]['blockName'] );
	}

	/**
	 * Test handling of 50-level deep nesting (stress test).
	 */
	public function test_parse_all_handles_50_level_nesting() {
		$depth    = 50;
		$content  = str_repeat( '<!-- wp:group -->', $depth );
		$content .= '<!-- wp:paragraph --><p>Very deep</p><!-- /wp:paragraph -->';
		$content .= str_repeat( '<!-- /wp:group -->', $depth );

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
	}

	/**
	 * Test handling of wide nesting (many siblings at each level).
	 */
	public function test_parse_all_handles_wide_nesting() {
		$siblings = 20;
		$content  = '<!-- wp:group --><div class="wp-block-group">';
		for ( $i = 0; $i < $siblings; $i++ ) {
			$content .= "<!-- wp:paragraph --><p>Paragraph {$i}</p><!-- /wp:paragraph -->";
		}
		$content .= '</div><!-- /wp:group -->';

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertCount( $siblings, $blocks[0]['innerBlocks'] );
	}

	/**
	 * Test handling of mixed nesting patterns.
	 */
	public function test_parse_all_handles_mixed_nesting() {
		$content  = '<!-- wp:columns --><div class="wp-block-columns">';
		$content .= '<!-- wp:column --><div class="wp-block-column">';
		$content .= '<!-- wp:group --><div class="wp-block-group">';
		$content .= '<!-- wp:paragraph --><p>Col1</p><!-- /wp:paragraph -->';
		$content .= '</div><!-- /wp:group -->';
		$content .= '</div><!-- /wp:column -->';
		$content .= '<!-- wp:column --><div class="wp-block-column">';
		$content .= '<!-- wp:heading --><h2>Col2</h2><!-- /wp:heading -->';
		$content .= '</div><!-- /wp:column -->';
		$content .= '</div><!-- /wp:columns -->';

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/columns', $blocks[0]['blockName'] );
		$this->assertCount( 2, $blocks[0]['innerBlocks'] ); // 2 columns.
	}

	// =========================================================================
	// Freeform Content Handling Tests (Task 8)
	// =========================================================================

	/**
	 * Test that parse_all skips pure freeform HTML content.
	 */
	public function test_parse_all_skips_freeform_html() {
		$content = '<p>Just HTML, no block markers</p>';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 0, $blocks );
	}

	/**
	 * Test that parse_all skips freeform content between blocks.
	 */
	public function test_parse_all_skips_freeform_between_blocks() {
		$content  = '<!-- wp:paragraph --><p>Block 1</p><!-- /wp:paragraph -->';
		$content .= '<p>Freeform between</p>';
		$content .= '<!-- wp:paragraph --><p>Block 2</p><!-- /wp:paragraph -->';

		$blocks = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 2, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
		$this->assertSame( 'core/paragraph', $blocks[1]['blockName'] );
	}

	/**
	 * Test that parse_all skips freeform content before first block.
	 */
	public function test_parse_all_skips_leading_freeform() {
		$content = '<p>Leading freeform</p><!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph -->';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
	}

	/**
	 * Test that parse_all skips freeform content after last block.
	 */
	public function test_parse_all_skips_trailing_freeform() {
		$content = '<!-- wp:paragraph --><p>Block</p><!-- /wp:paragraph --><p>Trailing freeform</p>';
		$blocks  = Block_Processor_Helper::parse_all( $content );

		$this->assertCount( 1, $blocks );
		$this->assertSame( 'core/paragraph', $blocks[0]['blockName'] );
	}

	/**
	 * Document: filter_empty_blocks removes freeform blocks from array.
	 */
	public function test_filter_empty_blocks_removes_freeform() {
		$blocks = [
			[
				'blockName' => null,
				'innerHTML' => '<p>Freeform</p>',
			],
			[
				'blockName' => 'core/paragraph',
				'attrs'     => [],
			],
			[
				'blockName' => null,
				'innerHTML' => 'Whitespace',
			],
			[
				'blockName' => 'core/heading',
				'attrs'     => [],
			],
		];

		$filtered = Block_Processor_Helper::filter_empty_blocks( $blocks );

		$this->assertCount( 2, $filtered );
		$this->assertSame( 'core/paragraph', $filtered[0]['blockName'] );
		$this->assertSame( 'core/heading', $filtered[1]['blockName'] );
	}
}
