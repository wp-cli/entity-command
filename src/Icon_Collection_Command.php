<?php

use WP_CLI\Formatter;

/**
 * Manages icon collections.
 *
 * Icon collections group registered SVG icons by source, and act as the
 * namespace of the icons belonging to them. WordPress core registers the
 * `core` collection, while themes and plugins register their own with
 * `wp_register_icon_collection()`. Because collections only exist for the
 * duration of a request, they cannot be created or deleted from the command
 * line.
 *
 * ## EXAMPLES
 *
 *     # List all icon collections
 *     $ wp icon collection list
 *     +------+-----------+--------------------------+-------+
 *     | slug | label     | description              | count |
 *     +------+-----------+--------------------------+-------+
 *     | core | WordPress | Default icon collection. | 88    |
 *     +------+-----------+--------------------------+-------+
 *
 *     # List the icons of a collection
 *     $ wp icon list --collection=core
 *
 * @package wp-cli
 */
class Icon_Collection_Command extends WP_CLI_Command {

	private $fields = [
		'slug',
		'label',
		'description',
		'count',
	];

	/**
	 * Lists registered icon collections.
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
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each collection:
	 *
	 * * slug
	 * * label
	 * * description
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     # List all icon collections
	 *     $ wp icon collection list
	 *     +------+-----------+--------------------------+-------+
	 *     | slug | label     | description              | count |
	 *     +------+-----------+--------------------------+-------+
	 *     | core | WordPress | Default icon collection. | 88    |
	 *     +------+-----------+--------------------------+-------+
	 *
	 *     # List collections in JSON format
	 *     $ wp icon collection list --fields=slug,label --format=json
	 *     [{"slug":"core","label":"WordPress"}]
	 *
	 * @subcommand list
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$collections = WP_Icon_Collections_Registry::get_instance()->get_all_registered();
		$counts      = $this->get_icon_counts();

		$items = [];

		foreach ( $collections as $collection ) {
			$items[] = $this->format_collection( $collection, $counts );
		}

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$formatter->display_items( wp_list_pluck( $items, 'slug' ) );
			return;
		}

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered icon collection.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Icon collection slug.
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
	 * * label
	 * * description
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific collection
	 *     $ wp icon collection get core
	 *     +-------------+--------------------------+
	 *     | Field       | Value                    |
	 *     +-------------+--------------------------+
	 *     | slug        | core                     |
	 *     | label       | WordPress                |
	 *     | description | Default icon collection. |
	 *     | count       | 88                       |
	 *     +-------------+--------------------------+
	 *
	 *     # Get the label field only
	 *     $ wp icon collection get core --field=label
	 *     WordPress
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$slug       = $args[0];
		$collection = WP_Icon_Collections_Registry::get_instance()->get_registered( $slug );

		if ( null === $collection ) {
			WP_CLI::error( "Icon collection {$slug} doesn't exist." );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $this->format_collection( $collection, $this->get_icon_counts() ) );
	}

	/**
	 * Checks if an icon collection is registered.
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Icon collection slug.
	 *
	 * ## EXAMPLES
	 *
	 *     # Bash script for checking if an icon collection is registered, with fallback.
	 *
	 *     if wp icon collection is-registered core 2>/dev/null; then
	 *         # Icon collection is registered. Do something.
	 *     else
	 *         # Fallback if collection is not registered.
	 *     fi
	 *
	 * @subcommand is-registered
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function is_registered( $args, $assoc_args ) {
		$slug = $args[0];

		if ( ! WP_Icon_Collections_Registry::get_instance()->is_registered( $slug ) ) {
			WP_CLI::halt( 1 );
		}

		WP_CLI::halt( 0 );
	}

	/**
	 * Counts the registered icons per collection.
	 *
	 * @return array<string, int>
	 */
	private function get_icon_counts(): array {
		$counts = [];

		foreach ( WP_Icons_Registry::get_instance()->get_registered_icons() as $icon ) {
			$slug = $this->to_string( $icon['collection'] ?? '' );

			if ( '' === $slug ) {
				continue;
			}

			$counts[ $slug ] = ( $counts[ $slug ] ?? 0 ) + 1;
		}

		return $counts;
	}

	/**
	 * @param array<string, mixed> $collection Registered icon collection properties.
	 * @param array<string, int> $counts Number of registered icons, keyed by collection slug.
	 * @return array<string, mixed>
	 */
	private function format_collection( array $collection, array $counts ): array {
		$slug = $this->to_string( $collection['slug'] ?? '' );

		return [
			'slug'        => $slug,
			'label'       => $this->to_string( $collection['label'] ?? '' ),
			'description' => $this->to_string( $collection['description'] ?? '' ),
			'count'       => $counts[ $slug ] ?? 0,
		];
	}

	/**
	 * @param mixed $value Value to convert.
	 */
	private function to_string( $value ): string {
		return is_string( $value ) ? $value : '';
	}

	/**
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	private function get_formatter( &$assoc_args ): Formatter {
		return new Formatter( $assoc_args, $this->fields, 'icon-collection' );
	}
}
