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

	/**
	 * List all terms associated with a post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID for the post.
	 *
	 * <taxonomy>...
	 * : One or more taxonomies to list.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each term.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific row fields.
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
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each term:
	 *
	 * * term_id
	 * * name
	 * * slug
	 * * taxonomy
	 *
	 * These fields are optionally available:
	 *
	 * * term_taxonomy_id
	 * * description
	 * * term_group
	 * * parent
	 * * count
	 *
	 * @subcommand list
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function list_( $args, $assoc_args ) {
		parent::list_( $args, $assoc_args );
	}

	/**
	 * Remove a term from a post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <taxonomy>
	 * : The name of the term's taxonomy.
	 *
	 * [<term>...]
	 * : The slug of the term or terms to be removed from the post.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: slug
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * [--all]
	 * : Remove all terms from the post.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function remove( $args, $assoc_args ) {
		parent::remove( $args, $assoc_args );
	}

	/**
	 * Add a term to a post.
	 *
	 * Append the term to the existing set of terms on the post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <taxonomy>
	 * : The name of the taxonomy type to be added.
	 *
	 * <term>...
	 * : The slug of the term or terms to be added.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: slug
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function add( $args, $assoc_args ) {
		parent::add( $args, $assoc_args );
	}

	/**
	 * Set post terms.
	 *
	 * Replaces existing terms on the post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <taxonomy>
	 * : The name of the taxonomy type to be updated.
	 *
	 * <term>...
	 * : The slug of the term or terms to be updated.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: slug
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function set( $args, $assoc_args ) {
		parent::set( $args, $assoc_args );
	}
}
