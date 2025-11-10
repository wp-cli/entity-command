<?php

use WP_CLI\Formatter;

/**
 * Retrieves information about registered ability categories.
 *
 * The Abilities API introduced in WordPress 6.9 organizes abilities into categories.
 * This command allows you to list and retrieve information about those categories.
 *
 * ## EXAMPLES
 *
 *     # List all registered ability categories
 *     $ wp ability category list --format=table
 *     +----------+-------------+
 *     | name     | description |
 *     +----------+-------------+
 *     | content  | Content operations |
 *     +----------+-------------+
 *
 *     # Get details about a specific category
 *     $ wp ability category get content --format=json
 *     {"name":"content","description":"Content operations"}
 *
 *     # Check if a category exists
 *     $ wp ability category exists content
 *     $ echo $?
 *     0
 *
 * @package wp-cli
 */
class Ability_Category_Command extends WP_CLI_Command {

	private $fields = array(
		'name',
		'description',
	);

	/**
	 * Lists registered ability categories.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields.
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
	 * These fields will be displayed by default for each category:
	 *
	 * * name
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered ability categories
	 *     $ wp ability category list --format=csv
	 *     name,description
	 *     content,Content operations
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		// Get all registered ability categories
		if ( function_exists( 'wp_get_ability_categories' ) ) {
			$categories = wp_get_ability_categories();
		} elseif ( function_exists( 'wp_ability_categories' ) ) {
			$registry   = wp_ability_categories();
			$categories = $registry->get_all();
		} else {
			$categories = array();
		}

		if ( empty( $categories ) ) {
			$categories = array();
		}

		$items = array();
		foreach ( $categories as $category ) {
			$items[] = $this->format_category_for_output( $category );
		}

		// Apply filters from $assoc_args
		$filter_keys = array_diff( array_keys( $assoc_args ), array( 'fields', 'field', 'format' ) );
		if ( ! empty( $filter_keys ) ) {
			$items = array_filter(
				$items,
				function ( $item ) use ( $assoc_args, $filter_keys ) {
					foreach ( $filter_keys as $key ) {
						if ( isset( $assoc_args[ $key ] ) && isset( $item[ $key ] ) ) {
							if ( $item[ $key ] !== $assoc_args[ $key ] ) {
								return false;
							}
						}
					}
					return true;
				}
			);
		}

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered ability category.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Category name.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole category, returns the value of a single field.
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
	 * These fields will be displayed by default for the specified category:
	 *
	 * * name
	 * * description
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific ability category
	 *     $ wp ability category get content --fields=name,description
	 *     +-------------+----------+
	 *     | Field       | Value    |
	 *     +-------------+----------+
	 *     | name        | content  |
	 *     | description | Content operations |
	 *     +-------------+----------+
	 *
	 *     # Get the description of a category
	 *     $ wp ability category get content --field=description
	 *     Content operations
	 */
	public function get( $args, $assoc_args ) {
		$category = wp_get_ability_category( $args[0] );

		if ( ! $category ) {
			WP_CLI::error( "Ability category {$args[0]} doesn't exist." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = $this->fields;
		}

		$formatter = $this->get_formatter( $assoc_args );

		$data = $this->format_category_for_output( $category );

		$formatter->display_item( $data );
	}

	/**
	 * Checks whether an ability category exists.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Category name.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether a category exists
	 *     $ wp ability category exists content
	 *     $ echo $?
	 *     0
	 *
	 *     # Check whether a non-existent category exists
	 *     $ wp ability category exists fake_category
	 *     $ echo $?
	 *     1
	 */
	public function exists( $args ) {
		if ( wp_has_ability_category( $args[0] ) ) {
			exit( 0 );
		} else {
			exit( 1 );
		}
	}

	/**
	 * Formats an ability category object for output.
	 *
	 * @param WP_Ability_Category $category The category object.
	 * @return array Formatted category data.
	 */
	private function format_category_for_output( $category ) {
		$data = array(
			'name'        => $category->name,
			'description' => $category->description,
		);

		return $data;
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'ability-category' );
	}
}
