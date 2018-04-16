<?php

/**
 * Adds, updates, deletes, and lists post custom fields.
 *
 * ## EXAMPLES
 *
 *     # Set post meta
 *     $ wp post meta set 123 _wp_page_template about.php
 *     Success: Updated custom field '_wp_page_template'.
 *
 *     # Get post meta
 *     $ wp post meta get 123 _wp_page_template
 *     about.php
 *
 *     # Update post meta
 *     $ wp post meta update 123 _wp_page_template contact.php
 *     Success: Updated custom field '_wp_page_template'.
 *
 *     # Delete post meta
 *     $ wp post meta delete 123 _wp_page_template
 *     Success: Deleted custom field.
 */
class Post_Meta_Command extends \WP_CLI\CommandWithMeta {
	protected $meta_type = 'post';

	/**
	 * Check that the post ID exists
	 *
	 * @param int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new \WP_CLI\Fetchers\Post;
		$post = $fetcher->get_check( $object_id );
		return $post->ID;
	}

	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $object_id, $meta_key, $meta_value, $unique );
	}

	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_post_meta( $object_id, $meta_key, $meta_value, $prev_value );
	}

	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		return get_post_meta( $object_id, $meta_key, $single );
	}

	protected function delete_metadata( $meta_type, $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return delete_post_meta( $object_id, $meta_key, $meta_value, $delete_all );
	}
}
