<?php

use WP_CLI\Formatter;

/**
 * Manages block patterns.
 *
 * Lists and gets information about registered block patterns.
 *
 * ## EXAMPLES
 *
 *     # List all registered block patterns
 *     $ wp pattern list
 *     +------------------+-------------------+
 *     | name             | title             |
 *     +------------------+-------------------+
 *     | core/text-three-columns | Three Columns of Text |
 *     +------------------+-------------------+
 *
 *     # Get details about a specific block pattern
 *     $ wp pattern get core/text-three-columns
 *
 * @package wp-cli
 */
class Pattern_Command extends WP_CLI_Command {

	private $fields = array(
		'name',
		'title',
	);

	/**
	 * Lists registered block patterns.
	 *
	 * ## OPTIONS
	 *
	 * [--category=<category>]
	 * : Filter patterns by category slug.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each pattern.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific pattern fields.
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
	 * These fields will be displayed by default for each pattern:
	 *
	 * * name
	 * * title
	 *
	 * These fields are optionally available:
	 *
	 * * description
	 * * content
	 * * categories
	 * * keywords
	 * * viewportWidth
	 * * blockTypes
	 * * inserter
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered block patterns
	 *     $ wp pattern list
	 *     +---------------------------+---------------------------+
	 *     | name                      | title                     |
	 *     +---------------------------+---------------------------+
	 *     | core/text-three-columns   | Three Columns of Text     |
	 *     +---------------------------+---------------------------+
	 *
	 *     # List patterns in a specific category
	 *     $ wp pattern list --category=buttons
	 *
	 *     # List patterns with all fields
	 *     $ wp pattern list --format=json
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$registry = WP_Block_Patterns_Registry::get_instance();
		$patterns = $registry->get_all_registered();

		// Filter by category if specified.
		if ( isset( $assoc_args['category'] ) ) {
			$category = $assoc_args['category'];
			$patterns = array_filter(
				$patterns,
				function ( $pattern ) use ( $category ) {
					return isset( $pattern['categories'] ) && in_array( $category, $pattern['categories'], true );
				}
			);
			unset( $assoc_args['category'] );
		}

		$items = array();
		foreach ( $patterns as $pattern ) {
			$items[] = $this->prepare_pattern_for_output( $pattern );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block pattern.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Pattern name.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole pattern, returns the value of a single field.
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
	 * * name
	 * * title
	 * * description
	 * * content
	 * * categories
	 * * keywords
	 * * viewportWidth
	 * * blockTypes
	 * * inserter
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details about a specific block pattern.
	 *     $ wp pattern get core/text-three-columns
	 *     +-------------+---------------------------+
	 *     | Field       | Value                     |
	 *     +-------------+---------------------------+
	 *     | name        | core/text-three-columns   |
	 *     | title       | Three Columns of Text     |
	 *     | description | ...                       |
	 *     +-------------+---------------------------+
	 */
	public function get( $args, $assoc_args ) {
		$pattern_name = $args[0];
		$registry     = WP_Block_Patterns_Registry::get_instance();
		$pattern      = $registry->get_registered( $pattern_name );

		if ( ! $pattern ) {
			WP_CLI::error( "Block pattern '{$pattern_name}' is not registered." );
		}

		$data = $this->prepare_pattern_for_output( $pattern );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $data );
	}

	/**
	 * Prepares pattern data for output.
	 *
	 * @param array $pattern Pattern data.
	 * @return array Prepared pattern data.
	 */
	private function prepare_pattern_for_output( $pattern ) {
		return array(
			'name'          => $pattern['name'] ?? '',
			'title'         => $pattern['title'] ?? '',
			'description'   => $pattern['description'] ?? '',
			'content'       => $pattern['content'] ?? '',
			'categories'    => $pattern['categories'] ?? array(),
			'keywords'      => $pattern['keywords'] ?? array(),
			'viewportWidth' => $pattern['viewportWidth'] ?? null,
			'blockTypes'    => $pattern['blockTypes'] ?? array(),
			'inserter'      => $pattern['inserter'] ?? true,
		);
	}

	/**
	 * Gets a formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter Formatter instance.
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'pattern' );
	}
}
