<?php

use WP_CLI\CommandWithDBObject;
use WP_CLI\Fetchers\Post as PostFetcher;
use WP_CLI\Utils;
use WP_CLI\Entity\Diff;

/**
 * Manages revisions of posts.
 *
 * ## EXAMPLES
 *
 *     # List 2 revisions of post with ID `1`.
 *     $ wp post revision list 1 --latest=2
 *     +----+--------------+---------------+---------------------+-------------+
 *     | ID | post_title   | post_name     | post_date           | post_status |
 *     +----+--------------+---------------+---------------------+-------------+
 *     | 13 | Hello world! | 1-revision-v1 | 2019-09-01 14:37:16 | closed      |
 *     | 12 | Hello world! | 1-revision-v1 | 2019-09-01 14:37:00 | closed      |
 *     +----+--------------+---------------+---------------------+-------------+
 *
 *     # Get the revision ID, date and title.
 *     $ wp post revision get 837 --fields=ID,post_date,post_title
 *     +------------+---------------------+
 *     | Field      | Value               |
 *     +------------+---------------------+
 *     | ID         | 837                 |
 *     | post_date  | 2019-02-25 10:16:40 |
 *     | post_title | Home Page           |
 *     +------------+---------------------+
 *
 *     # Delete post revision.
 *     $ wp post revision delete 123
 *     Success: Deleted revision 123.
 *
 * @package wp-cli
 */
class Post_Revision_Command extends CommandWithDBObject {

	protected $obj_type   = 'revision';
	protected $obj_fields = [
		'ID',
		'post_title',
		'post_name',
		'post_date',
		'post_status',
	];

	public function __construct() {
		$this->fetcher = new PostFetcher();
	}

	/**
	 * Gets a list of revisions.
	 *
	 * ## OPTIONS
	 *
	 * <post-id>
	 * : Post ID to get revisions.
	 *
	 * [--latest[=<limit>]]
	 * : Returns revisions in latest first order. Also, can pass limit to fetch limited revisions.
	 *
	 * [--earliest[=<limit>]]
	 * : Returns revisions in earliest/oldest first order. Also, can pass limit to fetch limited revisions.
	 *
	 * ## EXAMPLES
	 *
	 *     # List 2 revisions of post with ID `1`.
	 *     $ wp post revision list 1 --latest=2
	 *     +----+--------------+---------------+---------------------+-------------+
	 *     | ID | post_title   | post_name     | post_date           | post_status |
	 *     +----+--------------+---------------+---------------------+-------------+
	 *     | 13 | Hello world! | 1-revision-v1 | 2019-09-01 14:37:16 | closed      |
	 *     | 12 | Hello world! | 1-revision-v1 | 2019-09-01 14:37:00 | closed      |
	 *     +----+--------------+---------------+---------------------+-------------+
	 *
	 *     # List post revisions in earliest first order.
	 *     $ wp post revision list 1 --earliest
	 *     +----+--------------+---------------+---------------------+-------------+
	 *     | ID | post_title   | post_name     | post_date           | post_status |
	 *     +----+--------------+---------------+---------------------+-------------+
	 *     | 3  | Hello world! | 1-revision-v1 | 2019-09-01 14:00:36 | closed      |
	 *     | 10 | Hello world! | 1-revision-v1 | 2019-09-01 14:34:36 | closed      |
	 *     | 11 | Hello world! | 1-revision-v1 | 2019-09-01 14:36:03 | closed      |
	 *     | 12 | Hello world! | 1-revision-v1 | 2019-09-01 14:37:00 | closed      |
	 *     | 13 | Hello world! | 1-revision-v1 | 2019-09-01 14:37:16 | closed      |
	 *     +----+--------------+---------------+---------------------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );
		$defaults  = [
			'post_status' => 'any',
			'post_type'   => 'revision',
			'post_parent' => $args[0],
		];

		// Read limit and order flag from `$assoc_args`.
		$order_limit_args = $this->read_order_limit_flag( $assoc_args );
		$query_args       = array_merge( $defaults, $order_limit_args );
		$query_args       = self::process_csv_arguments_to_arrays( $query_args );

		$revisions = new WP_Query( $query_args );
		$formatter->display_items( $revisions->posts );
	}

	/**
	 * Gets details about a revision.
	 *
	 * ## OPTIONS
	 *
	 * <revision-id>
	 * : The ID of the revision to get.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole post, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
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
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the revision ID, date and title.
	 *     $ wp post revision get 837 --fields=ID,post_date,post_title
	 *     +------------+---------------------+
	 *	   | Field      | Value               |
	 *     +------------+---------------------+
	 *     | ID         | 837                 |
	 *     | post_date  | 2019-02-25 10:16:40 |
	 *     | post_title | Home Page           |
	 *     +------------+---------------------+
	 */
	public function get( $args, $assoc_args ) {
		$post = $this->fetcher->get_check( $args[0] );

		$post_arr = get_object_vars( $post );
		unset( $post_arr['filter'] );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $post_arr );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $post_arr );
	}

	/**
	 * Deletes an existing post revision
	 *
	 * ## OPTIONS
	 *
	 * [<revision-ids>...]
	 * : One or more IDs of revisions to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete post revisions.
	 *     $ wp post revision delete 123 999
	 *     Success: Deleted revision 123.
	 *     Success: Deleted revision 999.
	 *
	 *     # Delete all revisions.
	 *     $ wp post revision delete
	 *     Success: Deleted revision 10.
	 *     Success: Deleted revision 19.
	 *     Success: Deleted revision 79.
	 *     Success: Deleted revision 123.
	 *     Success: Deleted revision 999.
	 */
	public function delete( $args, $assoc_args ) {
		if ( empty( $args ) ) {
			// Get all revision ids to delete all.
			$args = $this->get_all_revisions_ids();
		}

		$this->delete_revisions( $args );
	}

	/**
	 * Function used to delete a revisions.
	 *
	 * @param array $revision_ids Revision IDs.
	 * @return array
	 */
	private function delete_revisions( $revision_ids ) {
		foreach ( $revision_ids as $revision_id ) {
			$result = $this->delete_revision( $revision_id );
			$this->success_or_failure( $result );
		}
	}

	/**
	 * Function to delete single revision.
	 *
	 * @param int $revision_id Revision ID.
	 * @return array
	 */
	private function delete_revision( $revision_id ) {
		$post_type = get_post_type( $revision_id );

		if ( 'revision' !== $post_type ) {
			return [ 'error', "{$revision_id} This would not be revision ID. Please provide valid revision ID." ];
		}

		if ( ! wp_delete_post( $revision_id ) ) {
			return [ 'error', "Failed deleting post {$revision_id}." ];
		}

		return [ 'success', "Deleted revision {$revision_id}." ];
	}

	/**
	 * Prune the post revisions.
	 *
	 * [<post-ids>...]
	 * : One or more post IDs to delete respective revisions.
	 *
	 * [--post_type=<post_type>]
	 * : The post type. Default 'post'.
	 *
	 * [--latest[=<limit>]]
	 * : Select revisions of given posts in latest first order. Also, can pass limit to delete limited revisions.
	 *
	 * [--earliest[=<limit>]]
	 * : Select revisions of given posts in earliest/oldest first order. Also, can pass limit to delete limited revisions.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete earliest/oldest two revisions of each post.
	 *     $ wp post revision prune --earliest=2
	 *     Deleting revision for post #89.
	 *     Success: Deleted revision 96.
	 *     Warning: No revision found for post #114.
	 *     Deleting revision for post #118.
	 *     Success: Deleted revision 124.
	 *     Success: Deleted revision 125.
	 *
	 * @subcommand prune
	 */
	public function revision_prune( $args, $assoc_args ) {
		// Get all revision ids to delete all.
		$post_ids = $this->get_posts_ids( $args, $assoc_args );

		if ( empty( $post_ids ) ) {
			WP_CLI::error( 'No posts found.' );
		}

		foreach ( $post_ids as $post_id ) {
			$revision_ids = $this->get_post_revisions( $post_id, $assoc_args );

			if ( ! empty( $revision_ids ) ) {
				WP_CLI::log( WP_CLI::colorize( "%9Deleting revision for post #{$post_id}." ) );
				$this->delete_revisions( $revision_ids );
			} else {
				WP_CLI::warning( "No revision found for post #{$post_id}." );
			}
		}
	}

	/**
	 * Restore revision.
	 *
	 * <revision-id>
	 * : Revision ID to restore.
	 *
	 * ## EXAMPLES
	 *
	 *     # Restore revision 151.
	 *     $ wp post revision restore 151
	 *     Success: Post #118 restored to revision 151.
	 *
	 */
	public function restore( $args, $assoc_args ) {
		$post_id = wp_restore_post_revision( $args['0'] );

		if ( empty( $post_id ) ) {
			WP_CLI::error( "Unable to restore revision #{$args['0']}." );
		}

		WP_CLI::success( "Post #{$post_id} restored to revision #{$args['0']}." );
	}

	/**
	 * Show revision difference.
	 *
	 * <starting-revision-id>
	 * : Starting revision ID.
	 *
	 * <ending-revision-id>
	 * : Ending revision ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check diff between two revisions.
	 *     $ wp post revision diff 151 152
	 *
	 */
	public function diff( $args, $assoc_args ) {
		if ( false === $this->get_revison_diff( $args['0'], $args['1'] ) ) {
			WP_CLI::warning( 'No diff found.' );
		}
	}

	/**
	 * Function to read order and limit flag.
	 *
	 * @param array $assoc_args Associative array of revision command flags.
	 *
	 * @return array
	 */
	private function read_order_limit_flag( $assoc_args ) {
		// Post revision max limit.
		$limit = 100;
		$order = 'DESC';

		if ( isset( $assoc_args['latest'] ) ) {
			$limit = Utils\get_flag_value( $assoc_args, 'latest', $limit );
		} elseif ( isset( $assoc_args['earliest'] ) ) {
			$limit = Utils\get_flag_value( $assoc_args, 'earliest', $limit );
			$order = 'ASC';
		}

		return [
			'posts_per_page' => $limit,
			'order'          => $order,
		];
	}

	/**
	 * Function to get all revisions ids.
	 *
	 * @return array
	 */
	private function get_all_revisions_ids() {

		$offset       = 0;
		$limit        = 100;
		$revision_ids = [];
		$query_args   = [
			'post_type'      => 'revision',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => $limit,
			'order'          => 'ASC',
		];

		do {
			// Set offset in WP_Query args.
			$query_args['offset'] = $offset;
			// Fire WP_Query.
			$query_result = new WP_Query( $query_args );

			if ( ! empty( $query_result->posts ) ) {
				$revision_ids = array_merge( $revision_ids, $query_result->posts );
			}

			// Update offset to fetch next ids.
			$offset += $limit;
		} while ( ! empty( $query_result->posts ) );

		return $revision_ids;
	}

	/**
	 * Function to get post ids to remove their revisions.
	 *
	 * @param array $args       Array post ids.
	 * @param array $assoc_args Revision prune command flags.
	 * @return array
	 */
	private function get_posts_ids( $args, $assoc_args ) {
		if ( ! empty( $args ) ) {
			return $args;
		}

		$offset     = 0;
		$limit      = 100;
		$post_ids   = [];
		$query_args = [
			'post_type'      => Utils\get_flag_value( $assoc_args, 'post_type', 'post' ),
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => $limit,
			'order'          => 'ASC',
		];

		do {
			$query_args['offset'] = $offset;
			$query_result         = new WP_Query( $query_args );

			if ( ! empty( $query_result->posts ) ) {
				$post_ids = array_merge( $post_ids, $query_result->posts );
			}

			// Update offset to fetch next ids.
			$offset += $limit;
		} while ( ! empty( $query_result->posts ) );

		return $post_ids;
	}

	/**
	 * Function to get revisions of given post.
	 *
	 * @param int   $parent_post_id Post ID to get revisions.
	 * @param array $assoc_args     Array of flags.
	 * @return array
	 */
	private function get_post_revisions( $parent_post_id, $assoc_args ) {
		$post_args  = $this->read_order_limit_flag( $assoc_args );
		$query_args = [
			'post_type'   => 'revision',
			'post_status' => 'any',
			'fields'      => 'ids',
			'post_parent' => $parent_post_id,
		];
		$query_args = array_merge( $query_args, $post_args );
		// Get posts IDs.
		$query_results = new WP_Query( $query_args );
		return $query_results->posts;
	}

	/**
	 * Function to get the difference between two revisions.
	 *
	 * @param string $compare_from_post_id Compare from post ID.
	 * @param string $compare_to_post_id   Compare to post ID.
	 *
	 * @return array
	 */
	private function get_revison_diff( $compare_from_post_id, $compare_to_post_id ) {
		// Fetching starting revision.
		$compare_from = get_post( $compare_from_post_id );
		// Fetching ending revision.
		$compare_to   = get_post( $compare_to_post_id );
		// Validate both the posts.
		$this->validate_revisions_before_compare( $compare_from, $compare_to );
		// Allow these to be versioned
		$fields  = array(
			'post_title'   => 'Title',
			'post_content' => 'Content',
			'post_excerpt' => 'Excerpt',
		);

		$is_diff_found = false;
		// Loop to check diff in title, content and excerpt.
		foreach( $fields as $field => $name ) {
			// Get the text diff.
			$diff = $this->get_text_diff( $compare_from->$field, $compare_to->$field );
			// If diff found then only show.
			if ( $diff ) {
				$is_diff_found = true;
				WP_CLI::log( WP_CLI::colorize( "%G{$name}%n" ) );
				WP_CLI::log( $diff );
			}
		}

		if ( $is_diff_found ) {
			return true;
		}
		return false;
	}

	/**
	 * Function to compare text difference between post text.
	 *
	 * @return array
	 */
	private function get_text_diff( $left_string, $right_string ) {
		$left_string  = normalize_whitespace( $left_string );
		$right_string = normalize_whitespace( $right_string );

		return Diff::to_string( Diff::compare( $left_string, $right_string ) );
	}

	/**
	 * Function to validate compare from and to posts.
	 *
	 * @param string $compare_from Compare from post.
	 * @param string $compare_to   Compare to post.
	 *
	 * @return void
	 */
	private function validate_revisions_before_compare( $compare_from, $compare_to ) {
		// Error if starting revision not found.
		if ( ! $compare_from ) {
			WP_CLI::error( 'Starting revision not found.' );
		}
		// Error if ending revision not found.
		if ( ! $compare_to ) {
			WP_CLI::error( 'Ending revision not found.' );
		}
		// If both revisions not belong to same post then return error.
		if ( $compare_from->post_parent !== $compare_to->post_parent ) {
			WP_CLI::error( "Please provide revision ID's of same post." );
		}
	}

}
