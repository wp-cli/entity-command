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
	 * List all metadata associated with a post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID for the post.
	 *
	 * [--keys=<keys>]
	 * : Limit output to metadata of specific keys.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific row fields. Defaults to id,meta_key,meta_value.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * [--orderby=<fields>]
	 * : Set orderby which field.
	 * ---
	 * default: id
	 * options:
	 *  - id
	 *  - meta_key
	 *  - meta_value
	 * ---
	 *
	 * [--order=<order>]
	 * : Set ascending or descending order.
	 * ---
	 * default: asc
	 * options:
	 *  - asc
	 *  - desc
	 * ---
	 *
	 * [--unserialize]
	 * : Unserialize meta_value output.
	 *
	 * @subcommand list
	 *
	 * @param array{0: string} $args Positional arguments..
	 * @param array{keys?: string, fields?: string, format: 'table'|'csv'|'json'|'yaml'|'count', orderby: 'id'|'meta_key'|'meta_value', order: 'asc'|'desc', unserialize?: bool} $assoc_args Associative arguments.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function list_( $args, $assoc_args ) {
		parent::list_( $args, $assoc_args );
	}

	/**
	 * Get meta field value.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <key>
	 * : The name of the meta field to get.
	 *
	 * [--single]
	 * : Whether to return a single value.
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * @param array{0: string, 1: string} $args Positional arguments.
	 * @param array{single?: bool, format: 'table'|'csv'|'json'|'yaml'} $assoc_args Associative arguments.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function get( $args, $assoc_args ) {
		parent::get( $args, $assoc_args );
	}

	/**
	 * Delete a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * [<key>]
	 * : The name of the meta field to delete.
	 *
	 * [<value>]
	 * : The value to delete. If omitted, all rows with key will deleted.
	 *
	 * [--all]
	 * : Delete all meta for the post.
	 *
	 * @param array<string> $args Positional arguments.
	 * @param array{all?: bool} $assoc_args Associative arguments.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function delete( $args, $assoc_args ) {
		parent::delete( $args, $assoc_args );
	}

	/**
	 * Add a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <key>
	 * : The name of the meta field to create.
	 *
	 * [<value>]
	 * : The value of the meta field. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * @param array<string> $args Positional arguments.
	 * @param array{format: 'plaintext'|'json'} $assoc_args Associative arguments.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function add( $args, $assoc_args ) {
		parent::add( $args, $assoc_args );
	}

	/**
	 * Update a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <key>
	 * : The name of the meta field to update.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * @alias set
	 *
	 * @param array<string> $args Positional arguments.
	 * @param array{format: 'plaintext'|'json'} $assoc_args Associative arguments.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function update( $args, $assoc_args ) {
		parent::update( $args, $assoc_args );
	}

	/**
	 * Get a nested value from a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <key>
	 * : The name of the meta field to get.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to pluck.
	 *
	 * [--format=<format>]
	 * : The output format of the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 *   - yaml
	 * ---
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function pluck( $args, $assoc_args ) {
		parent::pluck( $args, $assoc_args );
	}

	/**
	 * Update a nested value for a meta field.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : Patch action to perform.
	 * ---
	 * options:
	 *   - insert
	 *   - update
	 *   - delete
	 * ---
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <key>
	 * : The name of the meta field to update.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to patch.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function patch( $args, $assoc_args ) {
		parent::patch( $args, $assoc_args );
	}

	/**
	 * Check that the post ID exists
	 *
	 * @param string|int $object_id
	 * @return int|never
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new PostFetcher();
		$post    = $fetcher->get_check( (string) $object_id );
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
	 *
	 * @phpstan-return ($single is true ? string : $meta_key is "" ? array<array<string>> : array<string>)
	 */
	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		// @phpstan-ignore return.type
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
	 *     # Delete duplicate post meta.
	 *     wp post meta clean-duplicates 1234 enclosure
	 *     Success: Cleaned up duplicate 'enclosure' meta values.
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
			WP_CLI::error( sprintf( 'No meta values found for \'%s\'.', $key ) );
		}

		$uniq_metas = array();
		$dupe_metas = array();
		foreach ( $metas as $meta ) {
			if ( ! isset( $uniq_metas[ $meta->meta_value ] ) ) {
				$uniq_metas[ $meta->meta_value ] = (int) $meta->meta_id;
			} else {
				$dupe_metas[] = (int) $meta->meta_id;
			}
		}

		if ( count( $dupe_metas ) ) {
			WP_CLI::confirm(
				sprintf(
					'Are you sure you want to delete %d duplicate meta values and keep %d valid meta value?',
					count( $dupe_metas ),
					count( $uniq_metas )
				)
			);
			foreach ( $dupe_metas as $meta_id ) {
				delete_metadata_by_mid( 'post', $meta_id );
				WP_CLI::log( sprintf( 'Deleted meta id %d.', $meta_id ) );
			}
			WP_CLI::success( sprintf( 'Cleaned up duplicate \'%s\' meta values.', $key ) );
		} else {
			WP_CLI::success(
				sprintf(
					'Nothing to clean up: found %d valid meta value and %d duplicates.',
					count( $uniq_metas ),
					count( $dupe_metas )
				)
			);
		}
	}
}
