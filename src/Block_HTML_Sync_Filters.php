<?php
/**
 * Default filters for synchronizing block HTML with attribute changes.
 *
 * This file contains the built-in filter callbacks that update block HTML
 * when attributes are changed via `wp post block update`. These are registered
 * as WordPress filters, allowing them to be modified, replaced, or extended
 * by custom code loaded via --require, plugins, or themes.
 *
 * @package WP_CLI\Entity
 */

namespace WP_CLI\Entity;

/**
 * Handles synchronization of block HTML with updated attributes.
 *
 * Each method is a filter callback for 'wp_cli_post_block_update_html'.
 * Methods check if they handle the given block type and return early if not.
 */
class Block_HTML_Sync_Filters {

	/**
	 * Registers all default sync filters.
	 *
	 * Called during command initialization to set up the built-in handlers.
	 * Users can remove these filters or add their own at different priorities.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'wp_cli_post_block_update_html', [ __CLASS__, 'sync_heading_level' ], 10, 3 );
		add_filter( 'wp_cli_post_block_update_html', [ __CLASS__, 'sync_list_type' ], 10, 3 );
	}

	/**
	 * Synchronizes heading HTML tag with the level attribute.
	 *
	 * When a heading's level attribute changes (e.g., from 2 to 3),
	 * this updates the HTML tag from <h2> to <h3>.
	 *
	 * @param array  $block      The block structure.
	 * @param array  $new_attrs  The newly applied attributes.
	 * @param string $block_name The block type name.
	 * @return array The block with synchronized HTML.
	 */
	public static function sync_heading_level( $block, $new_attrs, $block_name ) {
		if ( 'core/heading' !== $block_name ) {
			return $block;
		}

		if ( ! isset( $new_attrs['level'] ) ) {
			return $block;
		}

		$new_level = (int) $new_attrs['level'];
		if ( $new_level < 1 || $new_level > 6 ) {
			return $block;
		}

		$inner_html = $block['innerHTML'] ?? '';
		if ( empty( $inner_html ) ) {
			return $block;
		}

		// Replace opening and closing heading tags.
		// Pattern matches <h1> through <h6> with optional attributes.
		$updated_html = preg_replace(
			'/<h[1-6](\s[^>]*)?>/',
			"<h{$new_level}$1>",
			$inner_html
		);
		$updated_html = preg_replace(
			'/<\/h[1-6]>/',
			"</h{$new_level}>",
			$updated_html
		);

		if ( null !== $updated_html && $updated_html !== $inner_html ) {
			$block['innerHTML']    = $updated_html;
			$block['innerContent'] = [ $updated_html ];
		}

		return $block;
	}

	/**
	 * Synchronizes list HTML tag with the ordered attribute.
	 *
	 * When a list's ordered attribute changes, this updates the HTML
	 * from <ul> to <ol> or vice versa.
	 *
	 * @param array  $block      The block structure.
	 * @param array  $new_attrs  The newly applied attributes.
	 * @param string $block_name The block type name.
	 * @return array The block with synchronized HTML.
	 */
	public static function sync_list_type( $block, $new_attrs, $block_name ) {
		if ( 'core/list' !== $block_name ) {
			return $block;
		}

		if ( ! isset( $new_attrs['ordered'] ) ) {
			return $block;
		}

		$inner_html = $block['innerHTML'] ?? '';
		if ( empty( $inner_html ) ) {
			return $block;
		}

		$is_ordered = (bool) $new_attrs['ordered'];
		$new_tag    = $is_ordered ? 'ol' : 'ul';
		$old_tag    = $is_ordered ? 'ul' : 'ol';

		// Replace opening and closing list tags.
		$updated_html = preg_replace(
			"/<{$old_tag}(\s[^>]*)?>/",
			"<{$new_tag}$1>",
			$inner_html
		);
		$updated_html = preg_replace(
			"/<\/{$old_tag}>/",
			"</{$new_tag}>",
			$updated_html
		);

		if ( null !== $updated_html && $updated_html !== $inner_html ) {
			$block['innerHTML']    = $updated_html;
			$block['innerContent'] = [ $updated_html ];
		}

		return $block;
	}
}
