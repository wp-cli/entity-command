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

	private $fields = [
		'slug',
		'name',
		'description',
	];

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
	 * * categories
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

		$items = [];

		/**
		 * @var \WP_Font_Collection $collection
		 */
		foreach ( $collections as $collection ) {
			$data = $collection->get_data();

			if ( is_wp_error( $data ) ) {
				WP_CLI::warning( $data );
				continue;
			}

			$categories = $data['categories'] ?? [];
			$categories = implode(
				', ',
				array_map(
					static function ( $category ) {
						return "{$category['name']} ({$category['slug']})";
					},
					$categories
				)
			);

			$items[] = [
				'slug'        => $collection->slug,
				'name'        => $data['name'] ?? '',
				'description' => $data['description'] ?? '',
				'categories'  => $categories,
			];
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
	 * * categories
	 * * font_families
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

		if ( is_wp_error( $collection_data ) ) {
			WP_CLI::error( $collection_data );
		}

		$categories = $collection_data['categories'] ?? [];
		$categories = implode(
			', ',
			array_map(
				static function ( $category ) {
					return "{$category['name']} ({$category['slug']})";
				},
				$categories
			)
		);

		$data = [
			'slug'        => $collection->slug,
			'name'        => $collection_data['name'] ?? '',
			'description' => $collection_data['description'] ?? '',
			'categories'  => $categories,
		];

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

		$font_families = $collection_data['font_families'] ?? [];

		if ( empty( $font_families ) || ! is_array( $font_families ) ) {
			WP_CLI::error( 'No font families found in this collection.' );
		}

		$category = \WP_CLI\Utils\get_flag_value( $assoc_args, 'category' );

		$items = [];
		foreach ( $font_families as $family ) {
			$family_categories = $family['categories'] ?? [];
			if ( $category && ! in_array( $category, $family_categories, true ) ) {
				continue;
			}

			$settings = $family['font_family_settings'] ?? [];

			$items[] = [
				'slug'       => $settings['slug'] ?? '',
				'name'       => $settings['name'] ?? '',
				'fontFamily' => $settings['fontFamily'] ?? '',
				'categories' => implode( ', ', $settings['categories'] ?? [] ),
				'preview'    => $settings['preview'] ?? '',
			];
		}

		$fields    = [ 'slug', 'name', 'fontFamily', 'categories', 'preview' ];
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

		$categories = $collection_data['categories'] ?? null;

		if ( empty( $categories ) || ! is_array( $categories ) ) {
			WP_CLI::error( 'No categories found in this collection.' );
		}

		$fields    = [ 'slug', 'name' ];
		$formatter = new Formatter( $assoc_args, $fields, 'category' );
		$formatter->display_items( $categories );
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'font-collection' );
	}
}
