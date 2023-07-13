<?php

use WP_CLI\CommandWithTerms;
use WP_CLI\Fetchers\Post as PostFetcher;

/**
 * Adds, updates, removes, and lists post terms.
 *
 * ## EXAMPLES
 *
 *     # Set category post term `test` to the post ID 123
 *     $ wp post term set 123 test category
 *     Success: Set term.
 *
 *     # Set category post terms `test` and `apple` to the post ID 123
 *     $ wp post term set 123 test apple category
 *     Success: Set terms.
 *
 *     # List category post terms for the post ID 123
 *     $ wp post term list 123 category --fields=term_id,slug
 *     +---------+-------+
 *     | term_id | slug  |
 *     +---------+-------+
 *     | 2       | apple |
 *     | 3       | test  |
 *     +----------+------+
 *
 *     # Remove category post terms `test` and `apple` for the post ID 123
 *     $ wp post term remove 123 category test apple
 *     Success: Removed terms.
 *
 */
class Post_Term_Command extends CommandWithTerms {
	protected $obj_type = 'post';

	private $fetcher;

	public function __construct() {
		$this->fetcher = new PostFetcher();
	}

	protected function get_object_type() {
		$post = $this->fetcher->get_check( $this->get_obj_id() );

		return $post->post_type;
	}
}
