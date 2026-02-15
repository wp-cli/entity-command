<?php

use WP_CLI\Utils;

/**
 * Manages post revisions.
 *
 * ## EXAMPLES
 *
 *     # Restore a post revision
 *     $ wp post revision restore 123
 *     Success: Restored revision 123.
 *
 *     # Show diff between two revisions
 *     $ wp post revision diff 123 456
 *
 * @package wp-cli
 */
class Post_Revision_Command {

	/**
	 * Valid post fields that can be compared.
	 *
	 * @var array<string>
	 */
	private $valid_fields = [
		'post_title',
		'post_content',
		'post_excerpt',
		'post_name',
		'post_status',
		'post_type',
		'post_author',
		'post_date',
		'post_date_gmt',
		'post_modified',
		'post_modified_gmt',
		'post_parent',
		'menu_order',
		'comment_status',
		'ping_status',
	];

	/**
	 * Restores a post revision.
	 *
	 * ## OPTIONS
	 *
	 * <revision_id>
	 * : The revision ID to restore.
	 *
	 * ## EXAMPLES
	 *
	 *     # Restore a post revision
	 *     $ wp post revision restore 123
	 *     Success: Restored revision 123.
	 *
	 * @subcommand restore
	 *
	 * @param array{0: string} $args Positional arguments.
	 */
	public function restore( $args ) {
		$revision_id = (int) $args[0];

		// Get the revision post
		$revision = wp_get_post_revision( $revision_id );

		/**
		 * Work around https://core.trac.wordpress.org/ticket/64643.
		 * @var int $revision_id
		 */

		if ( ! $revision ) {
			WP_CLI::error( "Invalid revision ID {$revision_id}." );
		}

		// Restore the revision
		$restored_post_id = wp_restore_post_revision( $revision_id );

		// wp_restore_post_revision() returns post ID on success, false on failure, or null if revision is same as current
		if ( false === $restored_post_id ) {
			WP_CLI::error( "Failed to restore revision {$revision_id}." );
		}

		WP_CLI::success( "Restored revision {$revision_id}." );
	}

	/**
	 * Shows the difference between two revisions.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : The 'from' revision ID or post ID.
	 *
	 * [<to>]
	 * : The 'to' revision ID or post ID. If not provided, compares with the current post.
	 *
	 * [--field=<field>]
	 * : Compare specific field(s). Default: post_content
	 *
	 * ## EXAMPLES
	 *
	 *     # Show diff between two revisions
	 *     $ wp post revision diff 123 456
	 *
	 *     # Show diff between a revision and the current post
	 *     $ wp post revision diff 123
	 *
	 * @subcommand diff
	 *
	 * @param array{0: string, 1?: string} $args Positional arguments.
	 * @param array{field?: string} $assoc_args Associative arguments.
	 */
	public function diff( $args, $assoc_args ) {
		$from_id = (int) $args[0];
		$to_id   = isset( $args[1] ) ? (int) $args[1] : null;
		$field   = Utils\get_flag_value( $assoc_args, 'field', 'post_content' );

		// Get the 'from' revision or post
		$from_revision = wp_get_post_revision( $from_id );

		/**
		 * Work around https://core.trac.wordpress.org/ticket/64643.
		 * @var int $from_id
		 */

		if ( ! $from_revision instanceof \WP_Post ) {
			// Try as a regular post
			$from_revision = get_post( $from_id );
			if ( ! $from_revision instanceof \WP_Post ) {
				WP_CLI::error( "Invalid 'from' ID {$from_id}." );
			}
		}

		// Get the 'to' revision or post
		$to_revision = null;
		if ( $to_id ) {
			$to_revision = wp_get_post_revision( $to_id );

			/**
			 * Work around https://core.trac.wordpress.org/ticket/64643.
			 * @var int $to_id
			 */

			if ( ! $to_revision instanceof \WP_Post ) {
				// Try as a regular post
				$to_revision = get_post( $to_id );
				if ( ! $to_revision instanceof \WP_Post ) {
					WP_CLI::error( "Invalid 'to' ID {$to_id}." );
				}
			}
		} elseif ( 'revision' === $from_revision->post_type ) {
			// If no 'to' ID provided, use the parent post of the revision
			$to_revision = get_post( $from_revision->post_parent );
			if ( ! $to_revision instanceof \WP_Post ) {
				WP_CLI::error( "Could not find parent post for revision {$from_id}." );
			}
		} else {
			WP_CLI::error( "Please provide a 'to' revision ID when comparing posts." );
		}

		// Validate field
		if ( ! in_array( $field, $this->valid_fields, true ) ) {
			WP_CLI::error( "Invalid field '{$field}'. Valid fields: " . implode( ', ', $this->valid_fields ) );
		}

		// Get the field values - use isset to check if field exists on the object
		if ( ! isset( $from_revision->{$field} ) ) {
			WP_CLI::error( "Field '{$field}' not found on post/revision {$from_id}." );
		}

		// $to_revision is guaranteed to be non-null at this point due to earlier validation
		if ( ! isset( $to_revision->{$field} ) ) {
			$to_error_id = $to_id ?? $to_revision->ID;
			WP_CLI::error( "Field '{$field}' not found on revision/post {$to_error_id}." );
		}

		$left_string  = $from_revision->{$field};
		$right_string = $to_revision->{$field};

		// Split content into lines for diff
		$left_lines  = explode( "\n", $left_string );
		$right_lines = explode( "\n", $right_string );

		if ( ! class_exists( 'Text_Diff', false ) ) {
			// @phpstan-ignore constant.notFound
			require ABSPATH . WPINC . '/wp-diff.php';
		}

		// Create Text_Diff object
		$text_diff = new \Text_Diff( 'auto', [ $left_lines, $right_lines ] );

		// Check if there are any changes
		if ( 0 === $text_diff->countAddedLines() && 0 === $text_diff->countDeletedLines() ) {
			WP_CLI::success( 'No difference found.' );
			return;
		}

		// Display header
		WP_CLI::line(
			WP_CLI::colorize(
				sprintf(
					'%%y--- %s (%s) - ID %d%%n',
					$from_revision->post_title,
					$from_revision->post_modified,
					$from_revision->ID
				)
			)
		);
		WP_CLI::line(
			WP_CLI::colorize(
				sprintf(
					'%%y+++ %s (%s) - ID %d%%n',
					$to_revision->post_title,
					$to_revision->post_modified,
					$to_revision->ID
				)
			)
		);
		WP_CLI::line( '' );

		// Render the diff using CLI-friendly format
		$this->render_cli_diff( $text_diff );
	}

	/**
	 * Renders a diff in CLI-friendly format with colors.
	 *
	 * @param \Text_Diff $diff The diff object to render.
	 */
	private function render_cli_diff( $diff ) {
		$edits = $diff->getDiff();

		foreach ( $edits as $edit ) {
			switch ( get_class( $edit ) ) {
				case 'Text_Diff_Op_copy':
					// Unchanged lines - show in default color
					foreach ( $edit->orig as $line ) {
						WP_CLI::line( '  ' . $line );
					}
					break;

				case 'Text_Diff_Op_add':
					// Added lines - show in green
					foreach ( $edit->final as $line ) {
						WP_CLI::line( WP_CLI::colorize( '%g+ ' . $line . '%n' ) );
					}
					break;

				case 'Text_Diff_Op_delete':
					// Deleted lines - show in red
					foreach ( $edit->orig as $line ) {
						WP_CLI::line( WP_CLI::colorize( '%r- ' . $line . '%n' ) );
					}
					break;

				case 'Text_Diff_Op_change':
					// Changed lines - show deletions in red, additions in green
					foreach ( $edit->orig as $line ) {
						WP_CLI::line( WP_CLI::colorize( '%r- ' . $line . '%n' ) );
					}
					foreach ( $edit->final as $line ) {
						WP_CLI::line( WP_CLI::colorize( '%g+ ' . $line . '%n' ) );
					}
					break;
			}
		}
	}

	/**
	 * Deletes old post revisions.
	 *
	 * ## OPTIONS
	 *
	 * [<post-id>...]
	 * : One or more post IDs to prune revisions for. If not provided, prunes revisions for all posts.
	 *
	 * [--latest=<limit>]
	 * : Keep only the latest N revisions per post. Older revisions will be deleted.
	 *
	 * [--earliest=<limit>]
	 * : Keep only the earliest N revisions per post. Newer revisions will be deleted.
	 *
	 * [--yes]
	 * : Skip confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete all but the latest 5 revisions for post 123
	 *     $ wp post revision prune 123 --latest=5
	 *     Success: Deleted 3 revisions for post 123.
	 *
	 *     # Delete all but the latest 5 revisions for all posts
	 *     $ wp post revision prune --latest=5
	 *     Success: Deleted 150 revisions across 30 posts.
	 *
	 *     # Delete all but the earliest 2 revisions for posts 123 and 456
	 *     $ wp post revision prune 123 456 --earliest=2
	 *     Success: Deleted 5 revisions for post 123.
	 *     Success: Deleted 3 revisions for post 456.
	 *
	 * @subcommand prune
	 */
	public function prune( $args, $assoc_args ) {
		$latest   = Utils\get_flag_value( $assoc_args, 'latest', null );
		$earliest = Utils\get_flag_value( $assoc_args, 'earliest', null );

		// Validate flags
		if ( null === $latest && null === $earliest ) {
			WP_CLI::error( 'Please specify either --latest or --earliest flag.' );
		}

		if ( null !== $latest && null !== $earliest ) {
			WP_CLI::error( 'Cannot specify both --latest and --earliest flags.' );
		}

		$limit       = $latest ?? $earliest;
		$keep_latest = null !== $latest;

		if ( ! is_numeric( $limit ) || (int) $limit < 1 ) {
			WP_CLI::error( 'Limit must be a positive integer.' );
		}

		$limit = (int) $limit;

		// Get posts to process
		if ( ! empty( $args ) ) {
			$post_ids = array_map( 'intval', $args );
		} else {
			// Get all posts that have revisions
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$post_ids = $wpdb->get_col(
				"SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision' AND post_parent > 0"
			);
			$post_ids = array_map( 'intval', $post_ids );
		}

		if ( empty( $post_ids ) ) {
			WP_CLI::warning( 'No posts found with revisions.' );
			return;
		}

		// Confirm deletion if processing multiple posts without --yes flag
		if ( count( $post_ids ) > 1 && ! Utils\get_flag_value( $assoc_args, 'yes', false ) ) {
			WP_CLI::confirm(
				sprintf(
					'Are you sure you want to prune revisions for %d posts?',
					count( $post_ids )
				),
				$assoc_args
			);
		}

		$total_deleted   = 0;
		$posts_processed = 0;

		foreach ( $post_ids as $post_id ) {
			$deleted = $this->prune_post_revisions( $post_id, $limit, $keep_latest );

			if ( false === $deleted ) {
				WP_CLI::warning( "Post {$post_id} does not exist or has no revisions." );
				continue;
			}

			if ( $deleted > 0 ) {
				++$posts_processed;
				$total_deleted += $deleted;
				WP_CLI::success( "Deleted {$deleted} revision" . ( $deleted > 1 ? 's' : '' ) . " for post {$post_id}." );
			} elseif ( count( $post_ids ) === 1 ) {
				WP_CLI::success( "No revisions to delete for post {$post_id}." );
			}
		}

		if ( count( $post_ids ) > 1 ) {
			if ( $total_deleted > 0 ) {
				WP_CLI::success(
					sprintf(
						'Deleted %d revision%s across %d post%s.',
						$total_deleted,
						$total_deleted > 1 ? 's' : '',
						$posts_processed,
						$posts_processed > 1 ? 's' : ''
					)
				);
			} else {
				WP_CLI::success( 'No revisions to delete.' );
			}
		}
	}

	/**
	 * Prunes revisions for a single post.
	 *
	 * @param int  $post_id     The post ID.
	 * @param int  $limit       Number of revisions to keep.
	 * @param bool $keep_latest Whether to keep the latest revisions (true) or earliest (false).
	 * @return int|false Number of revisions deleted, or false if post not found.
	 */
	private function prune_post_revisions( $post_id, $limit, $keep_latest ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// Get all revisions for this post
		$revisions = wp_get_post_revisions( $post_id, [ 'order' => 'ASC' ] );

		if ( empty( $revisions ) ) {
			return false;
		}

		$revision_count = count( $revisions );

		// If we have fewer or equal revisions than the limit, nothing to delete
		if ( $revision_count <= $limit ) {
			return 0;
		}

		// Determine which revisions to delete
		$revisions_array = array_values( $revisions );

		if ( $keep_latest ) {
			// Keep the latest N, delete the rest (from beginning)
			$to_delete = array_slice( $revisions_array, 0, $revision_count - $limit );
		} else {
			// Keep the earliest N, delete the rest (from end)
			$to_delete = array_slice( $revisions_array, $limit );
		}

		$deleted = 0;
		foreach ( $to_delete as $revision ) {
			if ( $revision instanceof \WP_Post && wp_delete_post_revision( $revision->ID ) ) {
				++$deleted;
			}
		}

		return $deleted;
	}
}
