<?php

use WP_CLI\Formatter;

/**
 * Manages block types.
 *
 * Lists and gets information about registered block types in the WordPress block editor.
 *
 * ## EXAMPLES
 *
 *     # List all registered block types
 *     $ wp block list --format=csv
 *     name,title,description,category
 *     core/paragraph,Paragraph,"Start with the building block of all narrative.",text
 *
 *     # Get details about a specific block type
 *     $ wp block get core/paragraph --fields=name,title,category
 *     +----------+-----------+------+
 *     | name     | title     | category |
 *     +----------+-----------+------+
 *     | core/paragraph | Paragraph | text |
 *     +----------+-----------+------+
 *
 * @package wp-cli
 */
class Block_Command extends WP_CLI_Command {

	private $fields = [
		'name',
		'title',
		'description',
		'category',
	];

	/**
	 * Lists registered block types.
	 *
	 * ## OPTIONS
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each block type.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific block type fields.
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
	 * These fields will be displayed by default for each block type:
	 *
	 * * name
	 * * title
	 * * description
	 * * category
	 *
	 * These fields are optionally available:
	 *
	 * * parent
	 * * icon
	 * * keywords
	 * * textdomain
	 * * supports
	 * * styles
	 * * variations
	 * * api_version
	 * * editor_script
	 * * editor_style
	 * * script
	 * * style
	 *
	 * ## EXAMPLES
	 *
	 *     # List all registered block types
	 *     $ wp block list
	 *     +-------------------+-------------------+----------------------------------------+----------+
	 *     | name              | title             | description                            | category |
	 *     +-------------------+-------------------+----------------------------------------+----------+
	 *     | core/paragraph    | Paragraph         | Start with the building block of all.. | text     |
	 *     | core/heading      | Heading           | Introduce new sections and organize... | text     |
	 *     +-------------------+-------------------+----------------------------------------+----------+
	 *
	 *     # List all block types with 'text' category
	 *     $ wp block list --format=csv
	 *     name,title,description,category
	 *     core/paragraph,Paragraph,"Start with the building block of all narrative.",text
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$registry = WP_Block_Type_Registry::get_instance();
		$blocks   = $registry->get_all_registered();

		$items = [];
		foreach ( $blocks as $block ) {
			$items[] = $this->prepare_block_for_output( $block );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Gets details about a registered block type.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Block type name (e.g., core/paragraph).
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole block type, returns the value of a single field.
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
	 * * category
	 * * parent
	 * * icon
	 * * keywords
	 * * textdomain
	 * * supports
	 * * styles
	 * * variations
	 * * api_version
	 * * editor_script
	 * * editor_style
	 * * script
	 * * style
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details about the core/paragraph block type.
	 *     $ wp block get core/paragraph --fields=name,title,category
	 *     +----------------+-----------+----------+
	 *     | name           | title     | category |
	 *     +----------------+-----------+----------+
	 *     | core/paragraph | Paragraph | text     |
	 *     +----------------+-----------+----------+
	 */
	public function get( $args, $assoc_args ) {
		$block_name = $args[0];
		$registry   = WP_Block_Type_Registry::get_instance();
		$block      = $registry->get_registered( $block_name );

		if ( ! $block ) {
			WP_CLI::error( "Block type '{$block_name}' is not registered." );
		}

		$data = $this->prepare_block_for_output( $block );

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $data );
	}

	/**
	 * Prepares block data for output.
	 *
	 * @param WP_Block_Type $block Block type object.
	 * @return array Prepared block data.
	 */
	private function prepare_block_for_output( $block ) {
		return [
			'name'          => $block->name,
			'title'         => $block->title ?? '', // @phpstan-ignore-line (added in WP 5.5)
			'description'   => $block->description ?? '', // @phpstan-ignore-line (added in WP 5.5)
			'category'      => $block->category ?? '',
			'parent'        => $block->parent ?? null,
			'icon'          => $block->icon ?? '',
			'keywords'      => $block->keywords ?? [], // @phpstan-ignore-line (added in WP 5.5)
			'textdomain'    => $block->textdomain ?? '',
			'supports'      => $block->supports ?? [],
			'styles'        => $block->styles ?? [], // @phpstan-ignore-line (added in WP 5.5)
			'variations'    => $block->variations ?? [], // added in WP 5.8 and replaced with magic getter in 6.1
			'api_version'   => $block->api_version ?? 1, // @phpstan-ignore-line (added in WP 5.6)
			'editor_script' => $block->editor_script ?? '',
			'editor_style'  => $block->editor_style ?? '',
			'script'        => $block->script ?? '',
			'style'         => $block->style ?? '',
		];
	}

	/**
	 * Gets a formatter instance.
	 *
	 * @param array $assoc_args Associative arguments.
	 * @return Formatter Formatter instance.
	 */
	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'block' );
	}
}
