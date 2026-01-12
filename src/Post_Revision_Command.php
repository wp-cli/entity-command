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

		if ( false === $restored_post_id || null === $restored_post_id ) {
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
	 * : The 'to' revision ID. If not provided, compares with the current post.
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
			WP_CLI::error( "Field '{$field}' not found on revision {$from_id}." );
		}
		if ( ! isset( $to_revision->{$field} ) ) {
			WP_CLI::error( "Field '{$field}' not found on revision/post {$to_id}." );
		}

		$left_string  = $from_revision->{$field};
		$right_string = $to_revision->{$field};

		// Generate the diff
		$diff_args = [
			'title_left'  => sprintf(
				'%s (%s) - ID %d',
				$from_revision->post_title,
				$from_revision->post_modified,
				$from_revision->ID
			),
			'title_right' => sprintf(
				'%s (%s) - ID %d',
				$to_revision->post_title,
				$to_revision->post_modified,
				$to_revision->ID
			),
		];

		$diff = wp_text_diff( $left_string, $right_string, $diff_args );

		if ( ! $diff ) {
			WP_CLI::success( 'No difference found.' );
			return;
		}

		// Output the diff
		WP_CLI::line( $diff );
	}
}
