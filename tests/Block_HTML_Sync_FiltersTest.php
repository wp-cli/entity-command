<?php
/**
 * Tests for Block_HTML_Sync_Filters class.
 *
 * @package WP_CLI\Entity
 */

namespace WP_CLI\Entity\Tests;

use PHPUnit\Framework\TestCase;
use WP_CLI\Entity\Block_HTML_Sync_Filters;

/**
 * Test the Block_HTML_Sync_Filters class.
 */
class Block_HTML_Sync_FiltersTest extends TestCase {

	/**
	 * Reset global filter log before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		global $wpcli_entity_filter_test_log;
		$wpcli_entity_filter_test_log = [];
	}

	// =========================================================================
	// Tests for sync_heading_level()
	// =========================================================================

	/**
	 * Test that sync_heading_level updates heading tags across levels.
	 */
	public function test_sync_heading_level_updates_tags() {
		$transitions = [
			'h2 to h3' => [ 2, 3, '<h2>My Heading</h2>', '<h3>My Heading</h3>' ],
			'h3 to h1' => [ 3, 1, '<h3>Main Title</h3>', '<h1>Main Title</h1>' ],
			'h4 to h6' => [ 4, 6, '<h4>Sub Sub Heading</h4>', '<h6>Sub Sub Heading</h6>' ],
			'h6 to h2' => [ 6, 2, '<h6>Deep Section</h6>', '<h2>Deep Section</h2>' ],
			'h1 to h5' => [ 1, 5, '<h1>Document Title</h1>', '<h5>Document Title</h5>' ],
		];

		foreach ( $transitions as $desc => $data ) {
			list( $from_level, $to_level, $inner_html, $expected ) = $data;

			$block = [
				'blockName'    => 'core/heading',
				'attrs'        => [ 'level' => $from_level ],
				'innerHTML'    => $inner_html,
				'innerContent' => [ $inner_html ],
			];

			$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'level' => $to_level ], 'core/heading' );

			$this->assertSame( $expected, $result['innerHTML'], "Failed asserting innerHTML on {$desc}" );
			$this->assertSame( [ $expected ], $result['innerContent'], "Failed asserting innerContent on {$desc}" );
		}
	}

	/**
	 * Test that sync_heading_level preserves HTML attributes on the tag.
	 */
	public function test_sync_heading_level_preserves_attributes() {
		$inner_html = '<h2 class="has-text-align-center wp-block-heading" id="custom-anchor" style="color:#ff0000">Heading Text</h2>';
		$expected   = '<h4 class="has-text-align-center wp-block-heading" id="custom-anchor" style="color:#ff0000">Heading Text</h4>';

		$block = [
			'blockName'    => 'core/heading',
			'attrs'        => [ 'level' => 2 ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'level' => 4 ], 'core/heading' );

		$this->assertSame( $expected, $result['innerHTML'] );
		$this->assertSame( [ $expected ], $result['innerContent'] );
	}

	/**
	 * Test that sync_heading_level preserves nested formatting tags and text.
	 */
	public function test_sync_heading_level_preserves_nested_content() {
		$inner_html = '<h2>Hello <strong>World</strong> with <a href="https://wordpress.org">WordPress</a></h2>';
		$expected   = '<h3>Hello <strong>World</strong> with <a href="https://wordpress.org">WordPress</a></h3>';

		$block = [
			'blockName'    => 'core/heading',
			'attrs'        => [ 'level' => 2 ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'level' => 3 ], 'core/heading' );

		$this->assertSame( $expected, $result['innerHTML'] );
		$this->assertSame( [ $expected ], $result['innerContent'] );
	}

	/**
	 * Test that sync_heading_level ignores non-heading blocks.
	 */
	public function test_sync_heading_level_ignores_non_heading_block() {
		$block = [
			'blockName'    => 'core/paragraph',
			'attrs'        => [],
			'innerHTML'    => '<p>Some paragraph text</p>',
			'innerContent' => [ '<p>Some paragraph text</p>' ],
		];

		$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'level' => 3 ], 'core/paragraph' );

		$this->assertSame( $block, $result );
	}

	/**
	 * Test that sync_heading_level returns unmodified block when level attribute is missing.
	 */
	public function test_sync_heading_level_ignores_missing_level_attribute() {
		$block = [
			'blockName'    => 'core/heading',
			'attrs'        => [ 'level' => 2 ],
			'innerHTML'    => '<h2>Heading</h2>',
			'innerContent' => [ '<h2>Heading</h2>' ],
		];

		$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'content' => 'Updated text' ], 'core/heading' );

		$this->assertSame( $block, $result );
	}

	/**
	 * Test that sync_heading_level ignores out-of-range heading levels (< 1 or > 6).
	 */
	public function test_sync_heading_level_ignores_invalid_levels() {
		$invalid_levels = [ 0, -1, 7, 10 ];

		foreach ( $invalid_levels as $invalid_level ) {
			$block = [
				'blockName'    => 'core/heading',
				'attrs'        => [ 'level' => 2 ],
				'innerHTML'    => '<h2>Heading</h2>',
				'innerContent' => [ '<h2>Heading</h2>' ],
			];

			$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'level' => $invalid_level ], 'core/heading' );

			$this->assertSame( $block, $result, "Failed on invalid level {$invalid_level}" );
		}
	}

	/**
	 * Test that sync_heading_level handles empty innerHTML gracefully.
	 */
	public function test_sync_heading_level_handles_empty_inner_html() {
		$block_empty_string = [
			'blockName'    => 'core/heading',
			'attrs'        => [ 'level' => 2 ],
			'innerHTML'    => '',
			'innerContent' => [],
		];

		$result = Block_HTML_Sync_Filters::sync_heading_level( $block_empty_string, [ 'level' => 3 ], 'core/heading' );
		$this->assertSame( $block_empty_string, $result );

		$block_missing_key = [
			'blockName' => 'core/heading',
			'attrs'     => [ 'level' => 2 ],
		];

		$result_missing = Block_HTML_Sync_Filters::sync_heading_level( $block_missing_key, [ 'level' => 3 ], 'core/heading' );
		$this->assertSame( $block_missing_key, $result_missing );
	}

	/**
	 * Test that sync_heading_level leaves block intact when new level is identical to current.
	 */
	public function test_sync_heading_level_unchanged_when_same_level() {
		$inner_html = '<h2>Current Level</h2>';
		$block      = [
			'blockName'    => 'core/heading',
			'attrs'        => [ 'level' => 2 ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_heading_level( $block, [ 'level' => 2 ], 'core/heading' );

		$this->assertSame( $inner_html, $result['innerHTML'] );
		$this->assertSame( [ $inner_html ], $result['innerContent'] );
	}

	// =========================================================================
	// Tests for sync_list_type()
	// =========================================================================

	/**
	 * Test that sync_list_type converts unordered list to ordered list.
	 */
	public function test_sync_list_type_converts_unordered_to_ordered() {
		$inner_html = '<ul><li>First</li><li>Second</li></ul>';
		$expected   = '<ol><li>First</li><li>Second</li></ol>';

		$block = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => false ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'ordered' => true ], 'core/list' );

		$this->assertSame( $expected, $result['innerHTML'] );
		$this->assertSame( [ $expected ], $result['innerContent'] );
	}

	/**
	 * Test that sync_list_type converts ordered list to unordered list.
	 */
	public function test_sync_list_type_converts_ordered_to_unordered() {
		$inner_html = '<ol><li>First</li><li>Second</li></ol>';
		$expected   = '<ul><li>First</li><li>Second</li></ul>';

		$block = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => true ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'ordered' => false ], 'core/list' );

		$this->assertSame( $expected, $result['innerHTML'] );
		$this->assertSame( [ $expected ], $result['innerContent'] );
	}

	/**
	 * Test that sync_list_type preserves list attributes.
	 */
	public function test_sync_list_type_preserves_attributes() {
		$inner_html = '<ul class="wp-block-list is-style-checkmark" id="my-list" data-role="checklist"><li>Item</li></ul>';
		$expected   = '<ol class="wp-block-list is-style-checkmark" id="my-list" data-role="checklist"><li>Item</li></ol>';

		$block = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => false ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'ordered' => true ], 'core/list' );

		$this->assertSame( $expected, $result['innerHTML'] );
		$this->assertSame( [ $expected ], $result['innerContent'] );
	}

	/**
	 * Test that sync_list_type preserves nested formatting and items.
	 */
	public function test_sync_list_type_preserves_nested_items() {
		$inner_html = '<ul><li>Item 1 with <a href="#">link</a></li><li>Item 2 with <strong>bold</strong></li></ul>';
		$expected   = '<ol><li>Item 1 with <a href="#">link</a></li><li>Item 2 with <strong>bold</strong></li></ol>';

		$block = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => false ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'ordered' => true ], 'core/list' );

		$this->assertSame( $expected, $result['innerHTML'] );
		$this->assertSame( [ $expected ], $result['innerContent'] );
	}

	/**
	 * Test that sync_list_type ignores non-list blocks.
	 */
	public function test_sync_list_type_ignores_non_list_block() {
		$block = [
			'blockName'    => 'core/paragraph',
			'attrs'        => [],
			'innerHTML'    => '<p>Some text</p>',
			'innerContent' => [ '<p>Some text</p>' ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'ordered' => true ], 'core/paragraph' );

		$this->assertSame( $block, $result );
	}

	/**
	 * Test that sync_list_type returns unmodified block when ordered attribute is missing.
	 */
	public function test_sync_list_type_ignores_missing_ordered_attribute() {
		$block = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => false ],
			'innerHTML'    => '<ul><li>Item</li></ul>',
			'innerContent' => [ '<ul><li>Item</li></ul>' ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'reversed' => true ], 'core/list' );

		$this->assertSame( $block, $result );
	}

	/**
	 * Test that sync_list_type handles empty innerHTML gracefully.
	 */
	public function test_sync_list_type_handles_empty_inner_html() {
		$block_empty_string = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => false ],
			'innerHTML'    => '',
			'innerContent' => [],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block_empty_string, [ 'ordered' => true ], 'core/list' );
		$this->assertSame( $block_empty_string, $result );

		$block_missing_key = [
			'blockName' => 'core/list',
			'attrs'     => [ 'ordered' => false ],
		];

		$result_missing = Block_HTML_Sync_Filters::sync_list_type( $block_missing_key, [ 'ordered' => true ], 'core/list' );
		$this->assertSame( $block_missing_key, $result_missing );
	}

	/**
	 * Test that sync_list_type returns unchanged block when list type matches requested state.
	 */
	public function test_sync_list_type_unchanged_when_same_state() {
		$inner_html = '<ul><li>Item</li></ul>';
		$block      = [
			'blockName'    => 'core/list',
			'attrs'        => [ 'ordered' => false ],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];

		$result = Block_HTML_Sync_Filters::sync_list_type( $block, [ 'ordered' => false ], 'core/list' );

		$this->assertSame( $inner_html, $result['innerHTML'] );
		$this->assertSame( [ $inner_html ], $result['innerContent'] );
	}

	// =========================================================================
	// Tests for register()
	// =========================================================================

	/**
	 * Test that register() hooks both sync callbacks to wp_cli_post_block_update_html.
	 */
	public function test_register_hooks() {
		global $wpcli_entity_filter_test_log;

		Block_HTML_Sync_Filters::register();

		$this->assertIsArray( $wpcli_entity_filter_test_log );
		$this->assertCount( 2, $wpcli_entity_filter_test_log );

		$this->assertSame( 'wp_cli_post_block_update_html', $wpcli_entity_filter_test_log[0]['hook_name'] );
		$this->assertSame( [ Block_HTML_Sync_Filters::class, 'sync_heading_level' ], $wpcli_entity_filter_test_log[0]['callback'] );
		$this->assertSame( 10, $wpcli_entity_filter_test_log[0]['priority'] );
		$this->assertSame( 3, $wpcli_entity_filter_test_log[0]['accepted_args'] );

		$this->assertSame( 'wp_cli_post_block_update_html', $wpcli_entity_filter_test_log[1]['hook_name'] );
		$this->assertSame( [ Block_HTML_Sync_Filters::class, 'sync_list_type' ], $wpcli_entity_filter_test_log[1]['callback'] );
		$this->assertSame( 10, $wpcli_entity_filter_test_log[1]['priority'] );
		$this->assertSame( 3, $wpcli_entity_filter_test_log[1]['accepted_args'] );
	}
}
