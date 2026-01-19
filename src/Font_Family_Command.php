<?php

use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Manages font families.
 *
 * Font families are groups of related fonts that share the same typeface design.
 * Each font family can have multiple font faces (different weights and styles).
 *
 * ## EXAMPLES
 *
 *     # List all font families
 *     $ wp font family list
 *     +----+-------------+------------------+
 *     | ID | post_title  | post_name        |
 *     +----+-------------+------------------+
 *     | 10 | Roboto      | roboto           |
 *     +----+-------------+------------------+
 *
 *     # Get details about a font family
 *     $ wp font family get 10
 *     +------------+-------------+
 *     | Field      | Value       |
 *     +------------+-------------+
 *     | ID         | 10          |
 *     | post_title | Roboto      |
 *     +------------+-------------+
 *
 *     # Create a new font family
 *     $ wp font family create --post_title="Open Sans" --post_name="open-sans"
 *     Success: Created font family 11.
 *
 *     # Delete a font family
 *     $ wp font family delete 11
 *     Success: Deleted font family 11.
 *
 * @package wp-cli
 */
class Font_Family_Command extends WP_CLI_Command {

	private $fields = array(
		'ID',
		'post_title',
		'post_name',
		'post_status',
		'post_date',
	);

	/**
	 * Lists font families.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Query.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each font family.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - ids
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each font family:
	 *
	 * * ID
	 * * post_title
	 * * post_name
	 * * post_status
	 * * post_date
	 *
	 * ## EXAMPLES
	 *
	 *     # List font families
	 *     $ wp font family list
	 *     +----+-------------+------------------+
	 *     | ID | post_title  | post_name        |
	 *     +----+-------------+------------------+
	 *     | 10 | Roboto      | roboto           |
	 *     +----+-------------+------------------+
	 *
	 *     # List font families in JSON format
	 *     $ wp font family list --format=json
	 *     [{"ID":10,"post_title":"Roboto","post_name":"roboto"}]
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		if ( ! post_type_exists( 'wp_font_family' ) ) {
			WP_CLI::error( 'Font Library is not available. Requires WordPress 6.5 or greater.' );
		}

		$formatter = $this->get_formatter( $assoc_args );

		$query_args = array(
			'post_type'      => 'wp_font_family',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);

		// Allow filtering by any WP_Query args.
		foreach ( $assoc_args as $key => $value ) {
			if ( ! in_array( $key, array( 'format', 'fields', 'field' ), true ) ) {
				$query_args[ $key ] = $value;
			}
		}

		$query = new WP_Query( $query_args );
		$items = $query->posts;

		if ( 'ids' === $formatter->format ) {
			$items = wp_list_pluck( $items, 'ID' );
		}

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a font family.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Font family ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole font family, returns the value of a single field.
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
	 *     # Get font family with ID 10
	 *     $ wp font family get 10
	 *     +------------+-------------+
	 *     | Field      | Value       |
	 *     +------------+-------------+
	 *     | ID         | 10          |
	 *     | post_title | Roboto      |
	 *     +------------+-------------+
	 */
	public function get( $args, $assoc_args ) {
		if ( ! post_type_exists( 'wp_font_family' ) ) {
			WP_CLI::error( 'Font Library is not available. Requires WordPress 6.5 or greater.' );
		}

		$font_family_id = $args[0];
		$post           = get_post( $font_family_id );

		if ( ! $post || 'wp_font_family' !== $post->post_type ) {
			WP_CLI::error( "Font family {$font_family_id} doesn't exist." );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $post );
	}

	/**
	 * Creates a new font family.
	 *
	 * ## OPTIONS
	 *
	 * --post_title=<title>
	 * : The font family name.
	 *
	 * [--post_name=<slug>]
	 * : The font family slug. If not provided, will be generated from the title.
	 *
	 * [--post_status=<status>]
	 * : The post status for the font family.
	 * ---
	 * default: publish
	 * options:
	 *   - publish
	 *   - draft
	 * ---
	 *
	 * [--post_content=<content>]
	 * : Font family settings in JSON format.
	 *
	 * [--porcelain]
	 * : Output just the new font family ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a new font family
	 *     $ wp font family create --post_title="Open Sans"
	 *     Success: Created font family 11.
	 *
	 *     # Create a font family with custom slug and output just the ID
	 *     $ wp font family create --post_title="Roboto" --post_name="roboto" --porcelain
	 *     12
	 */
	public function create( $args, $assoc_args ) {
		if ( ! post_type_exists( 'wp_font_family' ) ) {
			WP_CLI::error( 'Font Library is not available. Requires WordPress 6.5 or greater.' );
		}

		if ( ! isset( $assoc_args['post_title'] ) ) {
			WP_CLI::error( 'The --post_title parameter is required.' );
		}

		$post_data = array(
			'post_type'   => 'wp_font_family',
			'post_title'  => $assoc_args['post_title'],
			'post_status' => isset( $assoc_args['post_status'] ) ? $assoc_args['post_status'] : 'publish',
		);

		if ( isset( $assoc_args['post_name'] ) ) {
			$post_data['post_name'] = $assoc_args['post_name'];
		}

		if ( isset( $assoc_args['post_content'] ) ) {
			$post_data['post_content'] = $assoc_args['post_content'];
		}

		$font_family_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $font_family_id ) ) {
			WP_CLI::error( $font_family_id );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $font_family_id );
		} else {
			WP_CLI::success( "Created font family {$font_family_id}." );
		}
	}

	/**
	 * Updates an existing font family.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Font family ID.
	 *
	 * [--post_title=<title>]
	 * : The font family name.
	 *
	 * [--post_name=<slug>]
	 * : The font family slug.
	 *
	 * [--post_status=<status>]
	 * : The post status for the font family.
	 *
	 * [--post_content=<content>]
	 * : Font family settings in JSON format.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update a font family's name
	 *     $ wp font family update 10 --post_title="New Font Name"
	 *     Success: Updated font family 10.
	 */
	public function update( $args, $assoc_args ) {
		if ( ! post_type_exists( 'wp_font_family' ) ) {
			WP_CLI::error( 'Font Library is not available. Requires WordPress 6.5 or greater.' );
		}

		$font_family_id = $args[0];
		$post           = get_post( $font_family_id );

		if ( ! $post || 'wp_font_family' !== $post->post_type ) {
			WP_CLI::error( "Font family {$font_family_id} doesn't exist." );
		}

		$post_data = array(
			'ID' => $font_family_id,
		);

		$allowed_fields = array( 'post_title', 'post_name', 'post_status', 'post_content' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $assoc_args[ $field ] ) ) {
				$post_data[ $field ] = $assoc_args[ $field ];
			}
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result );
		}

		WP_CLI::success( "Updated font family {$font_family_id}." );
	}

	/**
	 * Deletes one or more font families.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more font family IDs to delete.
	 *
	 * [--force]
	 * : Skip the trash bin and permanently delete.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a font family
	 *     $ wp font family delete 10
	 *     Success: Deleted font family 10.
	 *
	 *     # Delete multiple font families
	 *     $ wp font family delete 10 11 12
	 *     Success: Deleted font family 10.
	 *     Success: Deleted font family 11.
	 *     Success: Deleted font family 12.
	 */
	public function delete( $args, $assoc_args ) {
		if ( ! post_type_exists( 'wp_font_family' ) ) {
			WP_CLI::error( 'Font Library is not available. Requires WordPress 6.5 or greater.' );
		}

		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$count  = 0;
		$errors = 0;
		foreach ( $args as $font_family_id ) {
			$post = get_post( $font_family_id );

			if ( ! $post || 'wp_font_family' !== $post->post_type ) {
				WP_CLI::warning( "Font family {$font_family_id} doesn't exist." );
				++$errors;
				continue;
			}

			$result = wp_delete_post( $font_family_id, $force );

			if ( ! $result ) {
				WP_CLI::warning( "Failed to delete font family {$font_family_id}." );
				++$errors;
			} else {
				WP_CLI::success( "Deleted font family {$font_family_id}." );
				++$count;
			}
		}

		Utils\report_batch_operation_results( 'font family', 'delete', count( $args ), $count, $errors );
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'font-family' );
	}
}
