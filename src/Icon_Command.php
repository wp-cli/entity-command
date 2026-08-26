<?php

use WP_CLI\Formatter;
use WP_CLI\Utils;

/**
 * Manages registered SVG icons.
 *
 * Icons are registered in code with `wp_register_icon()` by WordPress core,
 * themes, and plugins, and always belong to an icon collection. Because they
 * only exist for the duration of a request, they cannot be created or deleted
 * from the command line.
 *
 * Icon names are namespaced with the slug of the collection they belong to,
 * in the form `collection/icon-name`.
 *
 * ## EXAMPLES
 *
 *     # List all registered icons
 *     $ wp icon list
 *     +-----------------+------------+------------+
 *     | name            | label      | collection |
 *     +-----------------+------------+------------+
 *     | core/arrow-down | Arrow Down | core       |
 *     | core/plus       | Plus       | core       |
 *     +-----------------+------------+------------+
 *
 *     # Get details about a single icon
 *     $ wp icon get core/plus --fields=name,label,collection
 *     +------------+-----------+
 *     | Field      | Value     |
 *     +------------+-----------+
 *     | name       | core/plus |
 *     | label      | Plus      |
 *     | collection | core      |
 *     +------------+-----------+
 *
 *     # Render an icon at a given size, ready to be embedded in a template
 *     $ wp icon render core/plus --size=48 --label="Add item" > plus.svg
 *
 * @package wp-cli
 */
class Icon_Command extends WP_CLI_Command {

	/**
	 * Fields shown by default when listing icons.
	 *
	 * The SVG markup is left out on purpose, as it does not render well
	 * across many rows.
	 */
	private $fields = [
		'name',
		'label',
		'collection',
	];

	/**
	 * Fields shown by default when getting a single icon.
	 */
	private $item_fields = [
		'name',
		'label',
		'collection',
		'content',
	];

	/**
	 * Lists registered icons.
	 *
	 * ## OPTIONS
	 *
	 * [--collection=<slug>]
	 * : Only list icons belonging to the given collection.
	 *
	 * [--search=<term>]
	 * : Only list icons whose name or label matches the given term.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each icon.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific icon fields.
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
	 * These fields will be displayed by default for each icon:
	 *
	 * * name
	 * * label
	 * * collection
	 *
	 * These fields are optionally available:
	 *
	 * * content
	 * * file_path
	 *
	 * ## EXAMPLES
	 *
	 *     # List all icons of a specific collection
	 *     $ wp icon list --collection=core
	 *     +-----------------+------------+------------+
	 *     | name            | label      | collection |
	 *     +-----------------+------------+------------+
	 *     | core/arrow-down | Arrow Down | core       |
	 *     | core/plus       | Plus       | core       |
	 *     +-----------------+------------+------------+
	 *
	 *     # Search for icons by name or label
	 *     $ wp icon list --search=arrow-down --field=name
	 *     core/arrow-down-left
	 *     core/arrow-down-right
	 *     core/arrow-down
	 *
	 *     # Count the icons of a collection
	 *     $ wp icon list --collection=core --format=count
	 *     88
	 *
	 * @subcommand list
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$collection = Utils\get_flag_value( $assoc_args, 'collection' );
		$search     = $this->to_string( Utils\get_flag_value( $assoc_args, 'search', '' ) );

		if ( null !== $collection ) {
			$collection = $this->to_string( $collection );

			if ( ! WP_Icon_Collections_Registry::get_instance()->is_registered( $collection ) ) {
				WP_CLI::error( "Icon collection {$collection} doesn't exist." );
			}
		}

		$items = [];

		foreach ( WP_Icons_Registry::get_instance()->get_registered_icons( $search ) as $icon ) {
			if ( null !== $collection && ( $icon['collection'] ?? '' ) !== $collection ) {
				continue;
			}

			$items[] = $this->format_icon( $icon );
		}

		$formatter = new Formatter( $assoc_args, $this->fields, 'icon' );

		if ( 'ids' === $formatter->format ) {
			$formatter->display_items( wp_list_pluck( $items, 'name' ) );
			return;
		}

		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered icon.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Namespaced icon name, in the form "collection/icon-name".
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole icon, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields.
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
	 * These fields will be displayed by default for the specified icon:
	 *
	 * * name
	 * * label
	 * * collection
	 * * content
	 *
	 * This field is optionally available:
	 *
	 * * file_path
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific icon
	 *     $ wp icon get core/plus --fields=name,label,collection
	 *     +------------+-----------+
	 *     | Field      | Value     |
	 *     +------------+-----------+
	 *     | name       | core/plus |
	 *     | label      | Plus      |
	 *     | collection | core      |
	 *     +------------+-----------+
	 *
	 *     # Get the SVG markup of an icon, as it was registered
	 *     $ wp icon get core/plus --field=content
	 *     <svg xmlns="http://www.w3.org/2000/svg" viewbox="0 0 24 24">
	 *         <path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z" />
	 *     </svg>
	 *
	 *     # Find out where an icon is loaded from
	 *     $ wp icon get core/plus --field=file_path
	 *     /var/www/html/wp-includes/images/icon-library/plus.svg
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function get( $args, $assoc_args ) {
		$name = $args[0];
		$icon = WP_Icons_Registry::get_instance()->get_registered_icon( $name );

		if ( null === $icon ) {
			WP_CLI::error( "Icon {$name} doesn't exist." );
		}

		$formatter = new Formatter( $assoc_args, $this->item_fields, 'icon' );
		$formatter->display_item( $this->format_icon( $icon ) );
	}

	/**
	 * Checks if an icon is registered.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Namespaced icon name, in the form "collection/icon-name".
	 *
	 * ## EXAMPLES
	 *
	 *     # Bash script for checking if an icon is registered, with fallback.
	 *
	 *     if wp icon is-registered core/plus 2>/dev/null; then
	 *         # Icon is registered. Do something.
	 *     else
	 *         # Fallback if icon is not registered.
	 *     fi
	 *
	 * @subcommand is-registered
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function is_registered( $args, $assoc_args ) {
		$name = $args[0];

		if ( ! WP_Icons_Registry::get_instance()->is_registered( $name ) ) {
			WP_CLI::halt( 1 );
		}

		WP_CLI::halt( 0 );
	}

	/**
	 * Renders the SVG markup of a registered icon.
	 *
	 * Unlike `wp icon get <name> --field=content`, which prints the icon as it
	 * was registered, this applies the same sizing and accessibility attributes
	 * that WordPress applies when rendering the icon on the front end.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Namespaced icon name, in the form "collection/icon-name".
	 *
	 * [--size=<size>]
	 * : Width and height of the icon in pixels. Pass "none" to leave the
	 * intrinsic dimensions of the SVG untouched.
	 * ---
	 * default: 24
	 * ---
	 *
	 * [--class=<class>]
	 * : Additional CSS class names, separated by spaces.
	 *
	 * [--label=<label>]
	 * : Accessible label for the icon. Without it, the icon is rendered as
	 * decorative and hidden from assistive technology.
	 *
	 * ## EXAMPLES
	 *
	 *     # Render an icon at its default size of 24 pixels
	 *     $ wp icon render core/plus
	 *     <svg aria-hidden="true" focusable="false" height="24" width="24" xmlns="http://www.w3.org/2000/svg" viewbox="0 0 24 24">
	 *         <path d="M11 12.5V17.5H12.5V12.5H17.5V11H12.5V6H11V11H6V12.5H11Z" />
	 *     </svg>
	 *
	 *     # Render a larger icon with a CSS class and an accessible label
	 *     $ wp icon render core/plus --size=48 --class=my-icon --label="Add item" > plus.svg
	 *
	 *     # Keep the intrinsic dimensions of the SVG
	 *     $ wp icon render core/plus --size=none
	 *
	 * @param string[] $args Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function render( $args, $assoc_args ) {
		$name = $args[0];

		if ( ! WP_Icons_Registry::get_instance()->is_registered( $name ) ) {
			WP_CLI::error( "Icon {$name} doesn't exist." );
		}

		$raw_size = Utils\get_flag_value( $assoc_args, 'size', 24 );
		$size     = null;

		if ( 'none' !== $raw_size ) {
			if ( is_int( $raw_size ) ) {
				$size = $raw_size;
			} elseif ( is_string( $raw_size ) && ctype_digit( $raw_size ) ) {
				$size = (int) $raw_size;
			}

			if ( null === $size || $size < 1 ) {
				WP_CLI::error( 'The --size argument must be a positive integer or "none".' );
			}
		}

		$markup = wp_get_icon(
			$name,
			[
				'size'  => $size,
				'class' => $this->to_string( Utils\get_flag_value( $assoc_args, 'class', '' ) ),
				'label' => $this->to_string( Utils\get_flag_value( $assoc_args, 'label', '' ) ),
			]
		);

		if ( '' === $markup ) {
			WP_CLI::error( "Icon {$name} doesn't contain valid SVG markup." );
		}

		WP_CLI::line( $markup );
	}

	/**
	 * @param array<string, mixed> $icon Registered icon properties.
	 * @return array<string, string>
	 */
	private function format_icon( array $icon ): array {
		return [
			'name'       => $this->to_string( $icon['name'] ?? '' ),
			'label'      => $this->to_string( $icon['label'] ?? '' ),
			'collection' => $this->to_string( $icon['collection'] ?? '' ),
			'content'    => $this->to_string( $icon['content'] ?? '' ),
			'file_path'  => $this->to_string( $icon['file_path'] ?? '' ),
		];
	}

	/**
	 * @param mixed $value Value to convert.
	 */
	private function to_string( $value ): string {
		return is_string( $value ) ? $value : '';
	}
}
