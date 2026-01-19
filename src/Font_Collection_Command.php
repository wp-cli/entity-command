<?php

use WP_CLI\Formatter;

/**
 * Manages font collections.
 *
 * Font collections are predefined sets of fonts that can be used in WordPress.
 * Collections are registered by WordPress core or themes and cannot be created
 * or deleted via the command line.
 *
 * ## EXAMPLES
 *
 *     # List all font collections
 *     $ wp font collection list
 *     +------------------+-------------------+---------+
 *     | slug             | name              | count   |
 *     +------------------+-------------------+---------+
 *     | google-fonts     | Google Fonts      | 1500    |
 *     +------------------+-------------------+---------+
 *
 *     # Get details about a specific font collection
 *     $ wp font collection get google-fonts
 *     +-------+------------------+
 *     | Field | Value            |
 *     +-------+------------------+
 *     | slug  | google-fonts     |
 *     | name  | Google Fonts     |
 *     +-------+------------------+
 *
 * @package wp-cli
 */
class Font_Collection_Command extends WP_CLI_Command {

	private $fields = array(
		'slug',
		'name',
		'description',
	);

	/**
	 * Gets a safe value from collection data array.
	 *
	 * @param mixed  $data Collection data.
	 * @param string $key  Key to retrieve.
	 * @return string Value from data or empty string.
	 */
	private function get_safe_value( $data, $key ) {
		return is_array( $data ) && isset( $data[ $key ] ) ? $data[ $key ] : '';
	}

	/**
	 * Lists registered font collections.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each collection.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific collection fields.
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
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each collection:
	 *
	 * * slug
	 * * name
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # List all font collections
	 *     $ wp font collection list
	 *     +------------------+-------------------+
	 *     | slug             | name              |
	 *     +------------------+-------------------+
	 *     | google-fonts     | Google Fonts      |
	 *     +------------------+-------------------+
	 *
	 *     # List collections in JSON format
	 *     $ wp font collection list --format=json
	 *     [{"slug":"google-fonts","name":"Google Fonts"}]
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$font_library = WP_Font_Library::get_instance();
		$collections  = $font_library->get_font_collections();

		$items = array();
		foreach ( $collections as $collection ) {
			$data    = $collection->get_data();
			$items[] = array(
				'slug'        => $collection->slug,
				'name'        => $this->get_safe_value( $data, 'name' ),
				'description' => $this->get_safe_value( $data, 'description' ),
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered font collection.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Font collection slug.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole collection, returns the value of a single field.
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
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for the specified collection:
	 *
	 * * slug
	 * * name
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific collection
	 *     $ wp font collection get google-fonts
	 *     +-------+------------------+
	 *     | Field | Value            |
	 *     +-------+------------------+
	 *     | slug  | google-fonts     |
	 *     | name  | Google Fonts     |
	 *     +-------+------------------+
	 *
	 *     # Get the name field only
	 *     $ wp font collection get google-fonts --field=name
	 *     Google Fonts
	 */
	public function get( $args, $assoc_args ) {
		$slug         = $args[0];
		$font_library = WP_Font_Library::get_instance();
		$collection   = $font_library->get_font_collection( $slug );

		if ( ! $collection ) {
			WP_CLI::error( "Font collection {$slug} doesn't exist." );
		}

		$collection_data = $collection->get_data();

		$data = array(
			'slug'        => $collection->slug,
			'name'        => $this->get_safe_value( $collection_data, 'name' ),
			'description' => $this->get_safe_value( $collection_data, 'description' ),
		);

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $data );
	}

	/**
	 * Checks if a font collection is registered.
	 *
	 * ## EXAMPLES
	 *
	 *     # Bash script for checking if a font collection is registered, with fallback.
	 *
	 *     if wp font collection is-registered google-fonts 2>/dev/null; then
	 *         # Font collection is registered. Do something.
	 *     else
	 *         # Fallback if collection is not registered.
	 *     fi
	 *
	 * @subcommand is-registered
	 *
	 * @param string[] $args Positional arguments. Unused.
	 * @param array{network?: bool} $assoc_args Associative arguments.
	 */
	public function is_registered( $args, $assoc_args ) {
		$slug         = $args[0];
		$font_library = WP_Font_Library::get_instance();
		$collection   = $font_library->get_font_collection( $slug );

		if ( ! $collection ) {
			WP_CLI::halt( 1 );
		}

		WP_CLI::halt( 0 );
	}

	/**
	 * Lists font families in a collection.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Font collection slug.
	 *
	 * [--category=<slug>]
	 * : Filter by category slug.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each family.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific family fields.
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
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * slug
	 * * name
	 * * fontFamily
	 * * category
	 * * preview
	 *
	 * ## EXAMPLES
	 *
	 *     # List all font families in a collection
	 *     $ wp font collection list-families google-fonts
	 *
	 *     # List font families in a specific category
	 *     $ wp font collection list-families google-fonts --category=sans-serif
	 *
	 * @subcommand list-families
	 */
	public function list_families( $args, $assoc_args ) {
		$slug         = $args[0];
		$font_library = WP_Font_Library::get_instance();
		$collection   = $font_library->get_font_collection( $slug );

		if ( ! $collection ) {
			WP_CLI::error( "Font collection {$slug} doesn't exist." );
		}

		$collection_data = $collection->get_data();

		if ( is_wp_error( $collection_data ) ) {
			WP_CLI::error( $collection_data );
		}

		$font_families = $this->get_safe_value( $collection_data, 'font_families' );

		if ( empty( $font_families ) || ! is_array( $font_families ) ) {
			WP_CLI::error( 'No font families found in this collection.' );
		}

		// Filter by category if specified.
		$category = \WP_CLI\Utils\get_flag_value( $assoc_args, 'category' );
		if ( $category ) {
			$filtered = array();
			foreach ( $font_families as $family ) {
				if ( ! is_array( $family ) ) {
					continue;
				}
				$categories = isset( $family['category'] ) ? (array) $family['category'] : array();
				if ( in_array( $category, $categories, true ) ) {
					$filtered[] = $family;
				}
			}
			$font_families = $filtered;
		}

		$items = array();
		foreach ( $font_families as $family ) {
			if ( ! is_array( $family ) ) {
				continue;
			}
			$category_list = isset( $family['category'] ) ? (array) $family['category'] : array();
			$items[]       = array(
				'slug'       => isset( $family['slug'] ) ? $family['slug'] : '',
				'name'       => isset( $family['name'] ) ? $family['name'] : '',
				'fontFamily' => isset( $family['fontFamily'] ) ? $family['fontFamily'] : '',
				'category'   => implode( ', ', $category_list ),
				'preview'    => isset( $family['preview'] ) ? $family['preview'] : '',
			);
		}

		$fields    = array( 'slug', 'name', 'fontFamily', 'category', 'preview' );
		$formatter = new Formatter( $assoc_args, $fields, 'font-family' );
		$formatter->display_items( $items );
	}

	/**
	 * Lists categories in a collection.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Font collection slug.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each category.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific category fields.
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
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * slug
	 * * name
	 *
	 * ## EXAMPLES
	 *
	 *     # List all categories in a collection
	 *     $ wp font collection list-categories google-fonts
	 *     +-------------+--------------+
	 *     | slug        | name         |
	 *     +-------------+--------------+
	 *     | sans-serif  | Sans Serif   |
	 *     | display     | Display      |
	 *     +-------------+--------------+
	 *
	 * @subcommand list-categories
	 */
	public function list_categories( $args, $assoc_args ) {
		$slug         = $args[0];
		$font_library = WP_Font_Library::get_instance();
		$collection   = $font_library->get_font_collection( $slug );

		if ( ! $collection ) {
			WP_CLI::error( "Font collection {$slug} doesn't exist." );
		}

		$collection_data = $collection->get_data();

		if ( is_wp_error( $collection_data ) ) {
			WP_CLI::error( $collection_data );
		}

		$categories = $this->get_safe_value( $collection_data, 'categories' );

		if ( empty( $categories ) || ! is_array( $categories ) ) {
			WP_CLI::error( 'No categories found in this collection.' );
		}

		$items = array();
		foreach ( $categories as $category ) {
			$items[] = array(
				'slug' => isset( $category['slug'] ) ? $category['slug'] : '',
				'name' => isset( $category['name'] ) ? $category['name'] : '',
			);
		}

		$fields    = array( 'slug', 'name' );
		$formatter = new Formatter( $assoc_args, $fields, 'category' );
		$formatter->display_items( $items );
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'font-collection' );
	}
}
