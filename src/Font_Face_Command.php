<?php

use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Manages font faces.
 *
 * Font faces are individual variants (weight/style) of a font family.
 * Each font face belongs to a parent font family.
 *
 * ## EXAMPLES
 *
 *     # List all font faces
 *     $ wp font face list
 *     +----+-------------+-------------+
 *     | ID | post_title  | post_parent |
 *     +----+-------------+-------------+
 *     | 15 | Regular     | 10          |
 *     +----+-------------+-------------+
 *
 *     # Get details about a font face
 *     $ wp font face get 15
 *     +------------+-------------+
 *     | Field      | Value       |
 *     +------------+-------------+
 *     | ID         | 15          |
 *     | post_title | Regular     |
 *     +------------+-------------+
 *
 *     # Create a new font face
 *     $ wp font face create --post_parent=10 --post_title="Bold" --post_name="bold"
 *     Success: Created font face 16.
 *
 *     # Delete a font face
 *     $ wp font face delete 16
 *     Success: Deleted font face 16.
 *
 * @package wp-cli
 */
class Font_Face_Command extends WP_CLI_Command {

	private $fields = array(
		'ID',
		'post_title',
		'post_name',
		'post_parent',
		'post_status',
		'post_date',
	);

	/**
	 * Lists font faces.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to WP_Query.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each font face.
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
	 * These fields will be displayed by default for each font face:
	 *
	 * * ID
	 * * post_title
	 * * post_name
	 * * post_parent
	 * * post_status
	 * * post_date
	 *
	 * ## EXAMPLES
	 *
	 *     # List all font faces
	 *     $ wp font face list
	 *     +----+-------------+-------------+
	 *     | ID | post_title  | post_parent |
	 *     +----+-------------+-------------+
	 *     | 15 | Regular     | 10          |
	 *     +----+-------------+-------------+
	 *
	 *     # List font faces for a specific family
	 *     $ wp font face list --post_parent=10
	 *     +----+-------------+-------------+
	 *     | ID | post_title  | post_parent |
	 *     +----+-------------+-------------+
	 *     | 15 | Regular     | 10          |
	 *     | 16 | Bold        | 10          |
	 *     +----+-------------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$query_args = array(
			'post_type'      => 'wp_font_face',
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
	 * Gets details about a font face.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Font face ID.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole font face, returns the value of a single field.
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
	 *     # Get font face with ID 15
	 *     $ wp font face get 15
	 *     +------------+-------------+
	 *     | Field      | Value       |
	 *     +------------+-------------+
	 *     | ID         | 15          |
	 *     | post_title | Regular     |
	 *     +------------+-------------+
	 */
	public function get( $args, $assoc_args ) {
		$font_face_id = $args[0];
		$post         = get_post( $font_face_id );

		if ( ! $post || 'wp_font_face' !== $post->post_type ) {
			WP_CLI::error( "Font face {$font_face_id} doesn't exist." );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $post );
	}

	/**
	 * Creates a new font face.
	 *
	 * ## OPTIONS
	 *
	 * --post_parent=<parent-id>
	 * : The font family ID this face belongs to.
	 *
	 * --post_title=<title>
	 * : The font face name (e.g., "Regular", "Bold").
	 *
	 * [--post_name=<slug>]
	 * : The font face slug. If not provided, will be generated from the title.
	 *
	 * [--post_status=<status>]
	 * : The post status for the font face.
	 * ---
	 * default: publish
	 * options:
	 *   - publish
	 *   - draft
	 * ---
	 *
	 * [--post_content=<content>]
	 * : Font face settings in JSON format.
	 *
	 * [--porcelain]
	 * : Output just the new font face ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a new font face
	 *     $ wp font face create --post_parent=10 --post_title="Bold"
	 *     Success: Created font face 16.
	 *
	 *     # Create a font face and output just the ID
	 *     $ wp font face create --post_parent=10 --post_title="Italic" --porcelain
	 *     17
	 */
	public function create( $args, $assoc_args ) {
		if ( ! isset( $assoc_args['post_parent'] ) ) {
			WP_CLI::error( 'The --post_parent parameter is required.' );
		}

		if ( ! isset( $assoc_args['post_title'] ) ) {
			WP_CLI::error( 'The --post_title parameter is required.' );
		}

		// Verify parent font family exists.
		$parent_post = get_post( $assoc_args['post_parent'] );
		if ( ! $parent_post || 'wp_font_family' !== $parent_post->post_type ) {
			WP_CLI::error( "Font family {$assoc_args['post_parent']} doesn't exist." );
		}

		$post_data = array(
			'post_type'   => 'wp_font_face',
			'post_parent' => $assoc_args['post_parent'],
			'post_title'  => $assoc_args['post_title'],
			'post_status' => isset( $assoc_args['post_status'] ) ? $assoc_args['post_status'] : 'publish',
		);

		if ( isset( $assoc_args['post_name'] ) ) {
			$post_data['post_name'] = $assoc_args['post_name'];
		}

		if ( isset( $assoc_args['post_content'] ) ) {
			$post_data['post_content'] = $assoc_args['post_content'];
		}

		$font_face_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $font_face_id ) ) {
			WP_CLI::error( $font_face_id );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $font_face_id );
		} else {
			WP_CLI::success( "Created font face {$font_face_id}." );
		}
	}

	/**
	 * Updates an existing font face.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Font face ID.
	 *
	 * [--post_parent=<parent-id>]
	 * : The font family ID this face belongs to.
	 *
	 * [--post_title=<title>]
	 * : The font face name.
	 *
	 * [--post_name=<slug>]
	 * : The font face slug.
	 *
	 * [--post_status=<status>]
	 * : The post status for the font face.
	 *
	 * [--post_content=<content>]
	 * : Font face settings in JSON format.
	 *
	 * ## EXAMPLES
	 *
	 *     # Update a font face's name
	 *     $ wp font face update 15 --post_title="New Name"
	 *     Success: Updated font face 15.
	 */
	public function update( $args, $assoc_args ) {
		$font_face_id = $args[0];
		$post         = get_post( $font_face_id );

		if ( ! $post || 'wp_font_face' !== $post->post_type ) {
			WP_CLI::error( "Font face {$font_face_id} doesn't exist." );
		}

		$post_data = array(
			'ID' => $font_face_id,
		);

		// Verify parent font family if provided.
		if ( isset( $assoc_args['post_parent'] ) ) {
			$parent_post = get_post( $assoc_args['post_parent'] );
			if ( ! $parent_post || 'wp_font_family' !== $parent_post->post_type ) {
				WP_CLI::error( "Font family {$assoc_args['post_parent']} doesn't exist." );
			}
		}

		$allowed_fields = array( 'post_parent', 'post_title', 'post_name', 'post_status', 'post_content' );
		foreach ( $allowed_fields as $field ) {
			if ( isset( $assoc_args[ $field ] ) ) {
				$post_data[ $field ] = $assoc_args[ $field ];
			}
		}

		$result = wp_update_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result );
		}

		WP_CLI::success( "Updated font face {$font_face_id}." );
	}

	/**
	 * Deletes one or more font faces.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : One or more font face IDs to delete.
	 *
	 * [--force]
	 * : Skip the trash bin and permanently delete.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete a font face
	 *     $ wp font face delete 15
	 *     Success: Deleted font face 15.
	 *
	 *     # Delete multiple font faces
	 *     $ wp font face delete 15 16 17
	 *     Success: Deleted font face 15.
	 *     Success: Deleted font face 16.
	 *     Success: Deleted font face 17.
	 */
	public function delete( $args, $assoc_args ) {
		$force = Utils\get_flag_value( $assoc_args, 'force' );

		$count  = 0;
		$errors = 0;
		foreach ( $args as $font_face_id ) {
			$post = get_post( $font_face_id );

			if ( ! $post || 'wp_font_face' !== $post->post_type ) {
				WP_CLI::warning( "Font face {$font_face_id} doesn't exist." );
				++$errors;
				continue;
			}

			$result = wp_delete_post( $font_face_id, $force );

			if ( ! $result ) {
				WP_CLI::warning( "Failed to delete font face {$font_face_id}." );
				++$errors;
			} else {
				WP_CLI::success( "Deleted font face {$font_face_id}." );
				++$count;
			}
		}

		Utils\report_batch_operation_results( 'font face', 'delete', count( $args ), $count, $errors );
	}

	/**
	 * Installs a font face.
	 *
	 * Creates a new font face post with the specified settings.
	 *
	 * ## OPTIONS
	 *
	 * <family-id>
	 * : Font family ID.
	 *
	 * --src=<src>
	 * : Font face source URL or file path.
	 *
	 * [--font-family=<family>]
	 * : CSS font-family value.
	 *
	 * [--font-style=<style>]
	 * : CSS font-style value (e.g., normal, italic).
	 * ---
	 * default: normal
	 * ---
	 *
	 * [--font-weight=<weight>]
	 * : CSS font-weight value (e.g., 400, 700).
	 * ---
	 * default: 400
	 * ---
	 *
	 * [--font-display=<display>]
	 * : CSS font-display value.
	 *
	 * [--porcelain]
	 * : Output just the new font face ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Install a font face
	 *     $ wp font face install 42 --src="https://example.com/font.woff2" --font-weight=700 --font-style=normal
	 *     Success: Created font face 43.
	 *
	 *     # Install a font face with porcelain output
	 *     $ wp font face install 42 --src="font.woff2" --porcelain
	 *     44
	 */
	public function install( $args, $assoc_args ) {
		$family_id = $args[0];

		// Verify parent font family exists.
		$parent_post = get_post( $family_id );
		if ( ! $parent_post || 'wp_font_family' !== $parent_post->post_type ) {
			WP_CLI::error( "Font family {$family_id} doesn't exist." );
		}

		if ( ! isset( $assoc_args['src'] ) ) {
			WP_CLI::error( 'The --src parameter is required.' );
		}

		// Prepare font face settings.
		$face_settings = array(
			'src' => $assoc_args['src'],
		);

		if ( isset( $assoc_args['font-family'] ) ) {
			$face_settings['fontFamily'] = $assoc_args['font-family'];
		}

		$font_style                 = isset( $assoc_args['font-style'] ) ? $assoc_args['font-style'] : 'normal';
		$face_settings['fontStyle'] = $font_style;

		$font_weight                 = isset( $assoc_args['font-weight'] ) ? $assoc_args['font-weight'] : '400';
		$face_settings['fontWeight'] = $font_weight;

		if ( isset( $assoc_args['font-display'] ) ) {
			$face_settings['fontDisplay'] = $assoc_args['font-display'];
		}

		// Generate title.
		$title_parts = array( $font_weight, $font_style );
		$title       = implode( ' ', $title_parts );

		$post_data = array(
			'post_type'    => 'wp_font_face',
			'post_parent'  => $family_id,
			'post_title'   => $title,
			'post_status'  => 'publish',
			'post_content' => wp_json_encode( $face_settings ),
		);

		$font_face_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $font_face_id ) ) {
			WP_CLI::error( $font_face_id );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $font_face_id );
		} else {
			WP_CLI::success( "Created font face {$font_face_id}." );
		}
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'font-face' );
	}
}
