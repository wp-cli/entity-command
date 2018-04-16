<?php

/**
 * Adds, updates, deletes, and lists term custom fields.
 *
 * ## EXAMPLES
 *
 *     # Set term meta
 *     $ wp term meta set 123 bio "Mary is a WordPress developer."
 *     Success: Updated custom field 'bio'.
 *
 *     # Get term meta
 *     $ wp term meta get 123 bio
 *     Mary is a WordPress developer.
 *
 *     # Update term meta
 *     $ wp term meta update 123 bio "Mary is an awesome WordPress developer."
 *     Success: Updated custom field 'bio'.
 *
 *     # Delete term meta
 *     $ wp term meta delete 123 bio
 *     Success: Deleted custom field.
 */
class Term_Meta_Command extends \WP_CLI\CommandWithMeta {
	protected $meta_type = 'term';

	/**
	 * Check that the term ID exists
	 *
	 * @param int
	 */
	protected function check_object_id( $object_id ) {
		$term = get_term( $object_id );
		if ( ! $term ) {
			WP_CLI::error( "Could not find the term with ID {$object_id}." );
		}
		return $term->term_id;
	}

	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		return add_term_meta( $object_id, $meta_key, $meta_value, $unique );
	}

	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_term_meta( $object_id, $meta_key, $meta_value, $prev_value );
	}

	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		return get_term_meta( $object_id, $meta_key, $single );
	}

	protected function delete_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return delete_term_meta( $object_id, $meta_key, $meta_value, $delete_all );
	}

}
