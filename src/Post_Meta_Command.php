<?php

use WP_CLI\CommandWithMeta;
use WP_CLI\Fetchers\Post as PostFetcher;

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
class Post_Meta_Command extends CommandWithMeta {
	protected $meta_type = 'post';

	/**
	 * Check that the post ID exists
	 *
	 * @param int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new PostFetcher();
		$post    = $fetcher->get_check( $object_id );
		return $post->ID;
	}

	/**
	 * Wrapper method for add_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param bool   $unique     Optional, default is false. Whether the
	 *                           specified metadata key should be unique for the
	 *                           object. If true, and the object already has a
	 *                           value for the specified metadata key, no change
	 *                           will be made.
	 *
	 * @return int|false The meta ID on success, false on failure.
	 */
	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $object_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Wrapper method for update_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. If specified, only update existing
	 *                           metadata entries with the specified value.
	 *                           Otherwise, update all entries.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful
	 *                  update, false on failure.
	 */
	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_post_meta( $object_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Wrapper method for get_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id ID of the object the metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified,
	 *                          retrieve all metadata for the specified object.
	 * @param bool   $single    Optional, default is false. If true, return only
	 *                          the first value of the specified meta_key. This
	 *                          parameter has no effect if meta_key is not
	 *                          specified.
	 *
	 * @return mixed Single metadata value, or array of values.
	 */
	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		return get_post_meta( $object_id, $meta_key, $single );
	}

	/**
	 * Wrapper method for delete_metadata that can be overridden in sub classes.
	 *
	 * @param int    $object_id  ID of the object metadata is for
	 * @param string $meta_key   Metadata key
	 * @param mixed  $meta_value  Optional. Metadata value. Must be serializable
	 *                            if non-scalar. If specified, only delete
	 *                            metadata entries with this value. Otherwise,
	 *                            delete all entries with the specified meta_key.
	 *                            Pass `null, `false`, or an empty string to skip
	 *                            this check. For backward compatibility, it is
	 *                            not possible to pass an empty string to delete
	 *                            those entries with an empty string for a value.
	 *
	 * @return bool True on successful delete, false on failure.
	 */
	protected function delete_metadata( $object_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $object_id, $meta_key, $meta_value );
	}

	/**
	 * Cleans up duplicate post meta values on a post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID of the post to clean.
	 *
	 * <key>
	 * : Meta key to clean up.
	 *
	 * ## EXAMPLES
	 *
	 *     wp post meta clean-duplicates 1234 enclosure
	 *
	 * @subcommand clean-duplicates
	 */
	public function clean_duplicates( $args, $assoc_args ) {
		global $wpdb;

		list( $post_id, $key ) = $args;

		$metas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND post_id=%d",
				$key,
				$post_id
			)
		);

		if ( empty( $metas ) ) {
			WP_CLI::error( 'No enclosures found.' );
		}

		$uniq_enclosures = array();
		$dupe_enclosures = array();
		foreach ( $metas as $meta ) {
			if ( ! isset( $uniq_enclosures[ $meta->meta_value ] ) ) {
				$uniq_enclosures[ $meta->meta_value ] = (int) $meta->meta_id;
			} else {
				$dupe_enclosures[] = (int) $meta->meta_id;
			}
		}

		if ( count( $dupe_enclosures ) ) {
			WP_CLI::confirm(
				sprintf(
					'Are you sure you want to delete %d duplicate enclosures and keep %d valid enclosures?',
					count( $dupe_enclosures ),
					count( $uniq_enclosures )
				)
			);
			foreach ( $dupe_enclosures as $meta_id ) {
				delete_metadata_by_mid( 'post', $meta_id );
				WP_CLI::log( sprintf( 'Deleted meta id %d.', $meta_id ) );
			}
			WP_CLI::success( 'Cleaned up duplicate enclosures.' );
		} else {
			WP_CLI::success(
				sprintf(
					'Nothing to clean up: found %d valid enclosures and %d duplicate enclosures.',
					count( $uniq_enclosures ),
					count( $dupe_enclosures )
				)
			);
		}
	}
}
