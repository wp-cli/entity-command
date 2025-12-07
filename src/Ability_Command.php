<?php

use WP_CLI\Formatter;

/**
 * Retrieves information about registered abilities.
 *
 * The Abilities API introduced in WordPress 6.9 is a unified registry of callable WordPress
 * capabilities with defined inputs and outputs, built for AI integrations and developer automation.
 *
 * ## EXAMPLES
 *
 *     # List all registered abilities
 *     $ wp ability list --format=table
 *     +------------------+-------------------+-------------+
 *     | name             | category          | description |
 *     +------------------+-------------------+-------------+
 *     | get_post         | content           | Gets a post |
 *     +------------------+-------------------+-------------+
 *
 *     # Get details about a specific ability
 *     $ wp ability get get_post --format=json
 *     {"name":"get_post","category":"content","description":"Gets a post"}
 *
 *     # Check if an ability exists
 *     $ wp ability exists get_post
 *     $ echo $?
 *     0
 *
 *     # Execute an ability with JSON input
 *     $ wp ability execute get_post '{"id": 1}'
 *     Success: Ability executed successfully.
 *
 * @package wp-cli
 */
class Ability_Command extends WP_CLI_Command {

	private $fields = array(
		'name',
		'category',
		'description',
	);

	/**
	 * Lists registered abilities.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each ability.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific ability fields.
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
	 * These fields will be displayed by default for each ability:
	 *
	 * * name
	 * * category
	 * * description
	 *
	 * These fields are optionally available:
	 *
	 * * callback
	 * * input_schema
	 * * output_schema
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered abilities
	 *     $ wp ability list --format=csv
	 *     name,category,description
	 *     get_post,content,Gets a post
	 *
	 *     # List abilities in a specific category
	 *     $ wp ability list --category=content --fields=name,description
	 *     +----------+-------------+
	 *     | name     | description |
	 *     +----------+-------------+
	 *     | get_post | Gets a post |
	 *     +----------+-------------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		// Get all registered abilities
		if ( function_exists( 'wp_get_abilities' ) ) {
			$abilities = wp_get_abilities();
		} elseif ( function_exists( 'wp_abilities' ) ) {
			$registry  = wp_abilities();
			$abilities = $registry->get_all();
		} else {
			$abilities = array();
		}

		if ( empty( $abilities ) ) {
			$abilities = array();
		}

		$items = array();
		foreach ( $abilities as $ability ) {
			$items[] = $this->format_ability_for_output( $ability );
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
	 * Gets details about a registered ability.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Ability name.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole ability, returns the value of a single field.
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
	 * These fields will be displayed by default for the specified ability:
	 *
	 * * name
	 * * label
	 * * description
	 * * category
	 * * input_schema
	 * * output_schema
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of a specific ability
	 *     $ wp ability get get_post --fields=name,category,description
	 *     +-------------+----------+
	 *     | Field       | Value    |
	 *     +-------------+----------+
	 *     | name        | get_post |
	 *     | category    | content  |
	 *     | description | Gets a post |
	 *     +-------------+----------+
	 *
	 *     # Get the category of an ability
	 *     $ wp ability get get_post --field=category
	 *     content
	 */
	public function get( $args, $assoc_args ) {
		$ability = wp_get_ability( $args[0] );

		if ( ! $ability ) {
			WP_CLI::error( "Ability {$args[0]} doesn't exist." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$default_fields = array_merge(
				$this->fields,
				array(
					'callback',
					'input_schema',
					'output_schema',
				)
			);

			$assoc_args['fields'] = $default_fields;
		}

		$formatter = $this->get_formatter( $assoc_args );

		$data = $this->format_ability_for_output( $ability );

		$formatter->display_item( $data );
	}

	/**
	 * Checks whether an ability exists.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Ability name.
	 *
	 * ## EXAMPLES
	 *
	 *     # Check whether an ability exists
	 *     $ wp ability exists get_post
	 *     $ echo $?
	 *     0
	 *
	 *     # Check whether a non-existent ability exists
	 *     $ wp ability exists fake_ability
	 *     $ echo $?
	 *     1
	 */
	public function exists( $args ) {
		if ( wp_has_ability( $args[0] ) ) {
			exit( 0 );
		} else {
			exit( 1 );
		}
	}

	/**
	 * Executes an ability with the provided input.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Ability name.
	 *
	 * [<input>]
	 * : JSON input for the ability. If not provided, reads from STDIN.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Execute an ability with inline JSON input
	 *     $ wp ability execute get_post '{"id": 1}'
	 *
	 *     # Execute an ability with input from STDIN
	 *     $ echo '{"id": 1}' | wp ability execute get_post
	 *
	 *     # Execute an ability and get YAML output
	 *     $ wp ability execute get_post '{"id": 1}' --format=yaml
	 */
	public function execute( $args, $assoc_args ) {
		$ability_name = $args[0];

		if ( ! wp_has_ability( $ability_name ) ) {
			WP_CLI::error( "Ability {$ability_name} doesn't exist." );
		}

		// Get input from argument or STDIN
		$input_json = isset( $args[1] ) ? $args[1] : file_get_contents( 'php://stdin' );

		if ( empty( $input_json ) ) {
			$input = array();
		} else {
			$input = json_decode( $input_json, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				WP_CLI::error( 'Invalid JSON input: ' . json_last_error_msg() );
			}
		}

		/**
		 * Existence is checked above with wp_has_ability().
		 *
		 * @var \WP_Ability $ability
		 */
		$ability = wp_get_ability( $ability_name );

		$result = $ability->execute( $input );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		// Output the result
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'json';

		if ( 'json' === $format ) {
			WP_CLI::line( (string) wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
		} elseif ( 'yaml' === $format ) {
			// Convert to YAML-like output
			foreach ( (array) $result as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					WP_CLI::line( $key . ': ' . wp_json_encode( $value ) );
				} else {
					WP_CLI::line( $key . ': ' . $value );
				}
			}
		}
	}

	/**
	 * Formats an ability object for output.
	 *
	 * @param WP_Ability $ability The ability object.
	 * @return array Formatted ability data.
	 */
	private function format_ability_for_output( $ability ) {
		$data = array(
			'name'          => $ability->get_name(),
			'label'         => $ability->get_label(),
			'description'   => $ability->get_description(),
			'category'      => $ability->get_category(),
			'input_schema'  => wp_json_encode( $ability->get_input_schema() ),
			'output_schema' => wp_json_encode( $ability->get_output_schema() ),
		);

		return $data;
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'ability' );
	}
}
