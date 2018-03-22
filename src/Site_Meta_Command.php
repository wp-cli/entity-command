<?php

/**
 * Adds, updates, deletes, and lists site custom fields.
 *
 * ## EXAMPLES
 *
 *     # Set site meta
 *     $ wp site meta set 123 bio "Mary is a WordPress developer."
 *     Success: Updated custom field 'bio'.
 *
 *     # Get site meta
 *     $ wp site meta get 123 bio
 *     Mary is a WordPress developer.
 *
 *     # Update site meta
 *     $ wp site meta update 123 bio "Mary is an awesome WordPress developer."
 *     Success: Updated custom field 'bio'.
 *
 *     # Delete site meta
 *     $ wp site meta delete 123 bio
 *     Success: Deleted custom field.
 */
class Site_Meta_Command extends \WP_CLI\CommandWithMeta {
	protected $meta_type = 'blog';

	/**
	 * Check that the site ID exists
	 *
	 * @param int
	 */
	protected function check_object_id( $object_id ) {
		$fetcher = new \WP_CLI\Fetchers\Site;
		$site = $fetcher->get_check( $object_id );
		return $site->blog_id;
	}

}
