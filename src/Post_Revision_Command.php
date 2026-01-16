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
	 */
	public function restore( $args ) {
		$revision_id = (int) $args[0];

		// Get the revision post
		$revision = wp_get_post_revision( $revision_id );

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
	 */
	public function diff( $args, $assoc_args ) {
		$from_id = (int) $args[0];
		$to_id   = isset( $args[1] ) ? (int) $args[1] : null;
		$field   = Utils\get_flag_value( $assoc_args, 'field', 'post_content' );

		// Get the 'from' revision or post
		$from_revision = wp_get_post_revision( $from_id );
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
}
