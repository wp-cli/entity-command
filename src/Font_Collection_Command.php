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

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'font-collection' );
	}
}
