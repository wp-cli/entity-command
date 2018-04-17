<?php

/**
 * Adds, updates, deletes, and lists comment custom fields.
 *
 * ## EXAMPLES
 *
 *     # Set comment meta
 *     $ wp comment meta set 123 description "Mary is a WordPress developer."
 *     Success: Updated custom field 'description'.
 *
 *     # Get comment meta
 *     $ wp comment meta get 123 description
 *     Mary is a WordPress developer.
 *
 *     # Update comment meta
 *     $ wp comment meta update 123 description "Mary is an awesome WordPress developer."
 *     Success: Updated custom field 'description'.
 *
 *     # Delete comment meta
 *     $ wp comment meta delete 123 description
 *     Success: Deleted custom field.
 */
class Comment_Meta_Command extends \WP_CLI\CommandWithMeta {
	protected $meta_type = 'comment';

	/**
	 * @param $object_id
	 * @param $meta_key
	 * @param $meta_value
	 * @param bool $unique
	 *
	 * @return mixed
	 */
	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		return add_comment_meta( $object_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * @param $object_id
	 * @param $meta_key
	 * @param $meta_value
	 * @param string $prev_value
	 *
	 * @return mixed
	 */
	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_comment_meta( $object_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * @param $object_id
	 * @param string $meta_key
	 * @param bool $single
	 *
	 * @return mixed
	 */
	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		return get_comment_meta( $object_id, $meta_key, $single );
	}

	/**
	 * @param $object_id
	 * @param $meta_key
	 * @param string $meta_value
	 * @param bool $delete_all
	 *
	 * @return mixed
	 */
	protected function delete_metadata( $object_id, $meta_key, $meta_value = '', $delete_all = false ) {
		return delete_comment_meta( $object_id, $meta_key, $meta_value, $delete_all );
	}

	/**
	 * Check that the comment ID exists
	 *
	 * @param int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new \WP_CLI\Fetchers\Comment;
		$comment = $fetcher->get_check( $object_id );
		return $comment->comment_ID;
	}
}
