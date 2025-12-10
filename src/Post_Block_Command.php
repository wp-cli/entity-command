<?php

use Mustangostang\Spyc;
use WP_CLI\Formatter;
use WP_CLI\Fetchers\Post as PostFetcher;
use WP_CLI\Utils;

/**
 * Manages blocks within post content.
 *
 * Provides commands for inspecting, manipulating, and managing
 * Gutenberg blocks in post content.
 *
 * ## EXAMPLES
 *
 *     # List all blocks in a post.
 *     $ wp post block list 123
 *     +------------------+-------+
 *     | blockName        | count |
 *     +------------------+-------+
 *     | core/paragraph   | 2     |
 *     | core/heading     | 1     |
 *     +------------------+-------+
 *
 *     # Parse blocks in a post to JSON.
 *     $ wp post block parse 123 --format=json
 *
 *     # Insert a paragraph block.
 *     $ wp post block insert 123 core/paragraph --content="Hello World"
 *
 * @package wp-cli
 */
class Post_Block_Command extends WP_CLI_Command {

	/**
	 * @var PostFetcher
	 */
	private $fetcher;

	/**
	 * Default fields to display for block list.
	 *
	 * @var string[]
	 */
	protected $obj_fields = [
		'index',
		'blockName',
		'attrs',
	];

	public function __construct() {
		$this->fetcher = new PostFetcher();
	}

	/**
	 * Gets a single block by index.
	 *
	 * Retrieves the full structure of a block at the specified position.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <index>
	 * : The block index (0-indexed).
	 *
	 * [--raw]
	 * : Include innerHTML in output.
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
	 *     # Get the first block in a post.
	 *     $ wp post block get 123 0
	 *     {
	 *         "blockName": "core/paragraph",
	 *         "attrs": {},
	 *         "innerBlocks": []
	 *     }
	 *
	 *     # Get the third block (index 2) with attributes.
	 *     $ wp post block get 123 2
	 *     {
	 *         "blockName": "core/heading",
	 *         "attrs": {
	 *             "level": 2
	 *         },
	 *         "innerBlocks": []
	 *     }
	 *
	 *     # Get block as YAML format.
	 *     $ wp post block get 123 1 --format=yaml
	 *     blockName: core/image
	 *     attrs:
	 *       id: 456
	 *       sizeSlug: large
	 *     innerBlocks: []
	 *
	 *     # Get block with raw HTML content included.
	 *     $ wp post block get 123 0 --raw
	 *     {
	 *         "blockName": "core/paragraph",
	 *         "attrs": {},
	 *         "innerBlocks": [],
	 *         "innerHTML": "<p>Hello World</p>",
	 *         "innerContent": ["<p>Hello World</p>"]
	 *     }
	 *
	 * @subcommand get
	 */
	public function get( $args, $assoc_args ) {
		$post   = $this->fetcher->get_check( $args[0] );
		$index  = (int) $args[1];
		$blocks = parse_blocks( $post->post_content );

		// Filter out empty blocks (whitespace between blocks).
		$blocks = array_values(
			array_filter(
				$blocks,
				function ( $block ) {
					return ! empty( $block['blockName'] );
				}
			)
		);

		if ( $index < 0 || $index >= count( $blocks ) ) {
			WP_CLI::error( "Invalid index: {$index}. Post has " . count( $blocks ) . ' block(s) (0-indexed).' );
		}

		$block       = $blocks[ $index ];
		$include_raw = Utils\get_flag_value( $assoc_args, 'raw', false );

		if ( ! $include_raw ) {
			$block = $this->strip_inner_html( [ $block ] )[0];
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'json' );

		if ( 'yaml' === $format ) {
			echo Spyc::YAMLDump( $block, 2, 0, true );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			echo json_encode( $block, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
		}
	}

	/**
	 * Updates a block's attributes or content by index.
	 *
	 * Modifies a specific block without changing its type.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <index>
	 * : The block index to update (0-indexed).
	 *
	 * [--attrs=<attrs>]
	 * : Block attributes as JSON. Merges with existing attributes by default.
	 *
	 * [--content=<content>]
	 * : New innerHTML content for the block.
	 *
	 * [--replace-attrs]
	 * : Replace all attributes instead of merging.
	 *
	 * [--porcelain]
	 * : Output just the post ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Change a heading from h2 to h3.
	 *     $ wp post block update 123 0 --attrs='{"level":3}'
	 *     Success: Updated block at index 0 in post 123.
	 *
	 *     # Add alignment to an existing paragraph (merges with existing attrs).
	 *     $ wp post block update 123 1 --attrs='{"align":"center"}'
	 *     Success: Updated block at index 1 in post 123.
	 *
	 *     # Update the text content of a paragraph block.
	 *     $ wp post block update 123 2 --content="<p>Updated paragraph text</p>"
	 *     Success: Updated block at index 2 in post 123.
	 *
	 *     # Update both attributes and content at once.
	 *     $ wp post block update 123 0 --attrs='{"level":2}' --content="<h2>New Heading</h2>"
	 *     Success: Updated block at index 0 in post 123.
	 *
	 *     # Replace all attributes instead of merging (removes existing attrs).
	 *     $ wp post block update 123 0 --attrs='{"level":4}' --replace-attrs
	 *     Success: Updated block at index 0 in post 123.
	 *
	 *     # Get just the post ID for scripting.
	 *     $ wp post block update 123 0 --attrs='{"level":2}' --porcelain
	 *     123
	 *
	 * @subcommand update
	 */
	public function update( $args, $assoc_args ) {
		$post   = $this->fetcher->get_check( $args[0] );
		$index  = (int) $args[1];
		$blocks = parse_blocks( $post->post_content );

		// Filter out empty blocks but keep track of original indices.
		$filtered_blocks = [];
		$index_map       = [];
		foreach ( $blocks as $original_idx => $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$index_map[ count( $filtered_blocks ) ] = $original_idx;
				$filtered_blocks[]                      = $block;
			}
		}

		if ( $index < 0 || $index >= count( $filtered_blocks ) ) {
			WP_CLI::error( "Invalid index: {$index}. Post has " . count( $filtered_blocks ) . ' block(s) (0-indexed).' );
		}

		$original_idx = $index_map[ $index ];
		$block        = $blocks[ $original_idx ];

		$attrs_json    = Utils\get_flag_value( $assoc_args, 'attrs', null );
		$content       = Utils\get_flag_value( $assoc_args, 'content', null );
		$replace_attrs = Utils\get_flag_value( $assoc_args, 'replace-attrs', false );

		if ( null === $attrs_json && null === $content ) {
			WP_CLI::error( 'You must specify either --attrs or --content.' );
		}

		if ( null !== $attrs_json ) {
			$new_attrs = json_decode( $attrs_json, true );
			if ( null === $new_attrs ) {
				WP_CLI::error( 'Invalid JSON provided for --attrs.' );
			}

			if ( $replace_attrs ) {
				$block['attrs'] = $new_attrs;
			} else {
				$block['attrs'] = array_merge(
					is_array( $block['attrs'] ) ? $block['attrs'] : [],
					is_array( $new_attrs ) ? $new_attrs : []
				);
			}
		}

		if ( null !== $content ) {
			$block['innerHTML']    = $content;
			$block['innerContent'] = [ $content ];
		}

		$blocks[ $original_idx ] = $block;

		// @phpstan-ignore argument.type
		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $post->ID );
		} else {
			WP_CLI::success( "Updated block at index {$index} in post {$post->ID}." );
		}
	}

	/**
	 * Moves a block from one position to another.
	 *
	 * Reorders blocks within the post by moving a block from one index to another.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <from-index>
	 * : Current block index (0-indexed).
	 *
	 * <to-index>
	 * : Target position index (0-indexed).
	 *
	 * [--porcelain]
	 * : Output just the post ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Move the first block to the third position.
	 *     $ wp post block move 123 0 2
	 *     Success: Moved block from index 0 to index 2 in post 123.
	 *
	 *     # Move the last block (index 4) to the beginning.
	 *     $ wp post block move 123 4 0
	 *     Success: Moved block from index 4 to index 0 in post 123.
	 *
	 *     # Move a heading block from position 3 to position 1.
	 *     $ wp post block move 123 3 1
	 *     Success: Moved block from index 3 to index 1 in post 123.
	 *
	 *     # Move block and get post ID for scripting.
	 *     $ wp post block move 123 2 0 --porcelain
	 *     123
	 *
	 * @subcommand move
	 */
	public function move( $args, $assoc_args ) {
		$post       = $this->fetcher->get_check( $args[0] );
		$from_index = (int) $args[1];
		$to_index   = (int) $args[2];
		$blocks     = parse_blocks( $post->post_content );

		// Filter out empty blocks but keep track of original indices.
		$filtered_blocks = [];
		$index_map       = [];
		foreach ( $blocks as $original_idx => $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$index_map[ count( $filtered_blocks ) ] = $original_idx;
				$filtered_blocks[]                      = $block;
			}
		}

		$block_count = count( $filtered_blocks );

		if ( $from_index < 0 || $from_index >= $block_count ) {
			WP_CLI::error( "Invalid from-index: {$from_index}. Post has {$block_count} block(s) (0-indexed)." );
		}

		if ( $to_index < 0 || $to_index >= $block_count ) {
			WP_CLI::error( "Invalid to-index: {$to_index}. Post has {$block_count} block(s) (0-indexed)." );
		}

		if ( $from_index === $to_index ) {
			WP_CLI::warning( 'Source and destination indices are the same. No changes made.' );
			return;
		}

		// Work with the actual blocks array (including whitespace).
		$original_from = (int) $index_map[ $from_index ];
		$block_to_move = $blocks[ $original_from ];

		// Remove the block from original position.
		array_splice( $blocks, $original_from, 1 );

		// Recalculate index map after removal.
		$new_filtered  = [];
		$new_index_map = [];
		foreach ( $blocks as $idx => $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$new_index_map[ count( $new_filtered ) ] = (int) $idx;
				$new_filtered[]                          = $block;
			}
		}

		// Calculate the actual insertion position.
		if ( $to_index >= count( $new_filtered ) ) {
			// Insert at end.
			$insert_pos = count( $blocks );
		} else {
			$insert_pos = (int) $new_index_map[ $to_index ];
		}

		// Insert at new position.
		array_splice( $blocks, $insert_pos, 0, [ $block_to_move ] );

		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $post->ID );
		} else {
			WP_CLI::success( "Moved block from index {$from_index} to index {$to_index} in post {$post->ID}." );
		}
	}

	/**
	 * Exports block content to a file.
	 *
	 * Exports blocks from a post to a file for backup or migration.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to export blocks from.
	 *
	 * [--file=<file>]
	 * : Output file path. If not specified, outputs to STDOUT.
	 *
	 * [--format=<format>]
	 * : Export format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 *   - html
	 * ---
	 *
	 * [--raw]
	 * : Include innerHTML in JSON/YAML output.
	 *
	 * ## EXAMPLES
	 *
	 *     # Export blocks to a JSON file for backup.
	 *     $ wp post block export 123 --file=blocks.json
	 *     Success: Exported 5 blocks to blocks.json
	 *
	 *     # Export blocks to STDOUT as JSON.
	 *     $ wp post block export 123
	 *     {
	 *         "version": "1.0",
	 *         "generator": "wp-cli/entity-command",
	 *         "post_id": 123,
	 *         "exported_at": "2024-12-10T12:00:00+00:00",
	 *         "blocks": [...]
	 *     }
	 *
	 *     # Export as YAML format.
	 *     $ wp post block export 123 --format=yaml
	 *     version: "1.0"
	 *     generator: wp-cli/entity-command
	 *     blocks:
	 *       - blockName: core/paragraph
	 *         attrs: []
	 *
	 *     # Export rendered HTML (final output, not block structure).
	 *     $ wp post block export 123 --format=html --file=content.html
	 *     Success: Exported 5 blocks to content.html
	 *
	 *     # Export with raw innerHTML included for complete backup.
	 *     $ wp post block export 123 --raw --file=blocks-full.json
	 *     Success: Exported 5 blocks to blocks-full.json
	 *
	 *     # Pipe export to another command.
	 *     $ wp post block export 123 | jq '.blocks[].blockName'
	 *
	 * @subcommand export
	 */
	public function export( $args, $assoc_args ) {
		$post        = $this->fetcher->get_check( $args[0] );
		$file        = Utils\get_flag_value( $assoc_args, 'file', null );
		$format      = Utils\get_flag_value( $assoc_args, 'format', 'json' );
		$include_raw = Utils\get_flag_value( $assoc_args, 'raw', false );

		$blocks = parse_blocks( $post->post_content );

		// Filter out empty blocks.
		$blocks = array_values(
			array_filter(
				$blocks,
				function ( $block ) {
					return ! empty( $block['blockName'] );
				}
			)
		);

		$block_count = count( $blocks );

		if ( 'html' === $format ) {
			$output = '';
			foreach ( $blocks as $block ) {
				$output .= render_block( $block );
			}
		} else {
			if ( ! $include_raw ) {
				$blocks = $this->strip_inner_html( $blocks );
			}

			$export_data = [
				'version'     => '1.0',
				'generator'   => 'wp-cli/entity-command',
				'post_id'     => $post->ID,
				'exported_at' => gmdate( 'c' ),
				'blocks'      => $blocks,
			];

			if ( 'yaml' === $format ) {
				$output = Spyc::YAMLDump( $export_data, 2, 0, true );
			} else {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				$output = json_encode( $export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
			}
		}

		if ( null !== $file ) {
			$dir = dirname( $file );
			if ( ! empty( $dir ) && ! is_dir( $dir ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
				if ( ! mkdir( $dir, 0755, true ) ) {
					WP_CLI::error( "Could not create directory: {$dir}" );
				}
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$result = file_put_contents( $file, $output );
			if ( false === $result ) {
				WP_CLI::error( "Could not write to file: {$file}" );
			}

			$block_word = 1 === $block_count ? 'block' : 'blocks';
			WP_CLI::success( "Exported {$block_count} {$block_word} to {$file}" );
		} else {
			echo $output;
		}
	}

	/**
	 * Imports blocks from a file into a post.
	 *
	 * Imports blocks from a JSON or YAML file into a post's content.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to import blocks into.
	 *
	 * [--file=<file>]
	 * : Input file path. If not specified, reads from STDIN.
	 *
	 * [--position=<position>]
	 * : Where to insert imported blocks.
	 * ---
	 * default: end
	 * options:
	 *   - start
	 *   - end
	 * ---
	 *
	 * [--replace]
	 * : Replace all existing blocks instead of appending.
	 *
	 * [--porcelain]
	 * : Output just the number of blocks imported.
	 *
	 * ## EXAMPLES
	 *
	 *     # Import blocks from a JSON file, append to end of post.
	 *     $ wp post block import 123 --file=blocks.json
	 *     Success: Imported 5 blocks into post 123.
	 *
	 *     # Import blocks at the beginning of the post.
	 *     $ wp post block import 123 --file=blocks.json --position=start
	 *     Success: Imported 5 blocks into post 123.
	 *
	 *     # Replace all existing content with imported blocks.
	 *     $ wp post block import 123 --file=blocks.json --replace
	 *     Success: Imported 5 blocks into post 123.
	 *
	 *     # Import from STDIN (piped from another command).
	 *     $ cat blocks.json | wp post block import 123
	 *     Success: Imported 5 blocks into post 123.
	 *
	 *     # Copy blocks from one post to another.
	 *     $ wp post block export 123 | wp post block import 456
	 *     Success: Imported 5 blocks into post 456.
	 *
	 *     # Import YAML format.
	 *     $ wp post block import 123 --file=blocks.yaml
	 *     Success: Imported 3 blocks into post 123.
	 *
	 *     # Get just the count of imported blocks for scripting.
	 *     $ wp post block import 123 --file=blocks.json --porcelain
	 *     5
	 *
	 * @subcommand import
	 */
	public function import( $args, $assoc_args ) {
		$post     = $this->fetcher->get_check( $args[0] );
		$file     = Utils\get_flag_value( $assoc_args, 'file', null );
		$position = Utils\get_flag_value( $assoc_args, 'position', 'end' );
		$replace  = Utils\get_flag_value( $assoc_args, 'replace', false );

		if ( null !== $file ) {
			if ( ! file_exists( $file ) ) {
				WP_CLI::error( "File not found: {$file}" );
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$input = file_get_contents( $file );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$input = file_get_contents( 'php://stdin' );
		}

		if ( false === $input || '' === trim( $input ) ) {
			WP_CLI::error( 'No input data provided.' );
		}

		// Try to parse as JSON first, then YAML.
		$data = json_decode( $input, true );
		if ( null === $data ) {
			$data = Spyc::YAMLLoadString( $input );
		}

		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Invalid input format. Expected JSON or YAML.' );
		}

		// Handle export format (with metadata wrapper) or plain blocks array.
		$import_blocks = isset( $data['blocks'] ) ? $data['blocks'] : $data;

		if ( ! is_array( $import_blocks ) || empty( $import_blocks ) ) {
			WP_CLI::error( 'No blocks found in import data.' );
		}

		// Validate block structure.
		foreach ( $import_blocks as $idx => $block ) {
			if ( ! isset( $block['blockName'] ) ) {
				WP_CLI::error( "Invalid block structure at index {$idx}: missing blockName." );
			}
		}

		$imported_count = count( $import_blocks );

		if ( $replace ) {
			$blocks = $import_blocks;
		} else {
			$blocks = parse_blocks( $post->post_content );

			// Filter out empty blocks.
			$blocks = array_values(
				array_filter(
					$blocks,
					function ( $block ) {
						return ! empty( $block['blockName'] );
					}
				)
			);

			if ( 'start' === $position ) {
				$blocks = array_merge( $import_blocks, $blocks );
			} else {
				$blocks = array_merge( $blocks, $import_blocks );
			}
		}

		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $imported_count );
		} else {
			$block_word = 1 === $imported_count ? 'block' : 'blocks';
			WP_CLI::success( "Imported {$imported_count} {$block_word} into post {$post->ID}." );
		}
	}

	/**
	 * Counts blocks across multiple posts.
	 *
	 * Analyzes block usage across posts for site-wide reporting.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : Optional post IDs. If not specified, queries all posts.
	 *
	 * [--block=<block-name>]
	 * : Only count specific block type.
	 *
	 * [--post-type=<type>]
	 * : Limit to specific post type(s). Comma-separated.
	 * ---
	 * default: post,page
	 * ---
	 *
	 * [--post-status=<status>]
	 * : Post status to include.
	 * ---
	 * default: publish
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Count all blocks across published posts and pages.
	 *     $ wp post block count
	 *     +------------------+-------+-------+
	 *     | blockName        | count | posts |
	 *     +------------------+-------+-------+
	 *     | core/paragraph   | 1542  | 234   |
	 *     | core/heading     | 523   | 198   |
	 *     | core/image       | 312   | 156   |
	 *     +------------------+-------+-------+
	 *
	 *     # Count blocks in specific posts only.
	 *     $ wp post block count 123 456 789
	 *     +------------------+-------+-------+
	 *     | blockName        | count | posts |
	 *     +------------------+-------+-------+
	 *     | core/paragraph   | 8     | 3     |
	 *     | core/heading     | 3     | 2     |
	 *     +------------------+-------+-------+
	 *
	 *     # Count only paragraph blocks across the site.
	 *     $ wp post block count --block=core/paragraph --format=count
	 *     1542
	 *
	 *     # Count blocks in a custom post type.
	 *     $ wp post block count --post-type=product
	 *
	 *     # Count blocks in multiple post types.
	 *     $ wp post block count --post-type=post,page,product
	 *
	 *     # Count blocks including drafts.
	 *     $ wp post block count --post-status=draft
	 *
	 *     # Get count as JSON for further processing.
	 *     $ wp post block count --format=json
	 *     [{"blockName":"core/paragraph","count":1542,"posts":234}]
	 *
	 *     # Get total number of unique block types used.
	 *     $ wp post block count --format=count
	 *     15
	 *
	 * @subcommand count
	 */
	public function count( $args, $assoc_args ) {
		$block_filter = Utils\get_flag_value( $assoc_args, 'block', null );
		$post_types   = Utils\get_flag_value( $assoc_args, 'post-type', 'post,page' );
		$post_status  = Utils\get_flag_value( $assoc_args, 'post-status', 'publish' );
		$format       = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( ! empty( $args ) ) {
			$post_ids = array_map( 'intval', $args );
		} else {
			$query_args = [
				'post_type'      => explode( ',', $post_types ),
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'fields'         => 'ids',
			];

			$post_ids = get_posts( $query_args );
		}

		if ( empty( $post_ids ) ) {
			WP_CLI::warning( 'No posts found matching criteria.' );
			return;
		}

		$block_counts = [];
		$post_counts  = [];

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post || ! has_blocks( $post->post_content ) ) {
				continue;
			}

			$blocks = parse_blocks( $post->post_content );
			$this->aggregate_block_counts( $blocks, $block_counts, $post_counts, $post_id, $block_filter );
		}

		if ( empty( $block_counts ) ) {
			WP_CLI::warning( 'No blocks found in queried posts.' );
			return;
		}

		// Sort by count descending.
		arsort( $block_counts );

		// Handle single block filter with count format.
		if ( null !== $block_filter && 'count' === $format ) {
			$count = isset( $block_counts[ $block_filter ] ) ? $block_counts[ $block_filter ] : 0;
			WP_CLI::line( (string) $count );
			return;
		}

		$items = [];
		foreach ( $block_counts as $block_name => $count ) {
			$items[] = [
				'blockName' => $block_name,
				'count'     => $count,
				'posts'     => isset( $post_counts[ $block_name ] ) ? count( $post_counts[ $block_name ] ) : 0,
			];
		}

		if ( 'count' === $format ) {
			WP_CLI::line( (string) count( $items ) );
			return;
		}

		$formatter = new Formatter( $assoc_args, [ 'blockName', 'count', 'posts' ] );
		$formatter->display_items( $items );
	}

	/**
	 * Clones a block within a post.
	 *
	 * Duplicates an existing block and inserts it at a specified position.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * <source-index>
	 * : Index of the block to clone (0-indexed).
	 *
	 * [--position=<position>]
	 * : Where to insert the cloned block.
	 * ---
	 * default: after
	 * options:
	 *   - after
	 *   - before
	 *   - start
	 *   - end
	 * ---
	 *
	 * [--porcelain]
	 * : Output just the new block index.
	 *
	 * ## EXAMPLES
	 *
	 *     # Clone a block and insert immediately after it (default).
	 *     $ wp post block clone 123 2
	 *     Success: Cloned block to index 3 in post 123.
	 *
	 *     # Clone the first block and insert immediately before it.
	 *     $ wp post block clone 123 0 --position=before
	 *     Success: Cloned block to index 0 in post 123.
	 *
	 *     # Clone a block and insert at the end of the post.
	 *     $ wp post block clone 123 0 --position=end
	 *     Success: Cloned block to index 5 in post 123.
	 *
	 *     # Clone a block and insert at the start of the post.
	 *     $ wp post block clone 123 3 --position=start
	 *     Success: Cloned block to index 0 in post 123.
	 *
	 *     # Clone and get just the new block index for scripting.
	 *     $ wp post block clone 123 1 --porcelain
	 *     2
	 *
	 *     # Duplicate the hero section (first block) at the end for a footer.
	 *     $ wp post block clone 123 0 --position=end
	 *     Success: Cloned block to index 10 in post 123.
	 *
	 * @subcommand clone
	 */
	public function clone_block( $args, $assoc_args ) {
		$post         = $this->fetcher->get_check( $args[0] );
		$source_index = (int) $args[1];
		$position     = Utils\get_flag_value( $assoc_args, 'position', 'after' );
		$blocks       = parse_blocks( $post->post_content );

		// Filter out empty blocks but keep track of original indices.
		$filtered_blocks = [];
		$index_map       = [];
		foreach ( $blocks as $original_idx => $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$index_map[ count( $filtered_blocks ) ] = $original_idx;
				$filtered_blocks[]                      = $block;
			}
		}

		$block_count = count( $filtered_blocks );

		if ( $source_index < 0 || $source_index >= $block_count ) {
			WP_CLI::error( "Invalid source-index: {$source_index}. Post has {$block_count} block(s) (0-indexed)." );
		}

		$original_idx = (int) $index_map[ $source_index ];
		$cloned_block = $this->deep_copy_block( $blocks[ $original_idx ] );

		// Calculate insertion position.
		switch ( $position ) {
			case 'before':
				$insert_pos = $original_idx;
				$new_index  = $source_index;
				break;
			case 'after':
				$insert_pos = $original_idx + 1;
				$new_index  = $source_index + 1;
				break;
			case 'start':
				$insert_pos = 0;
				$new_index  = 0;
				break;
			case 'end':
			default:
				$insert_pos = count( $blocks );
				$new_index  = $block_count;
				break;
		}

		array_splice( $blocks, $insert_pos, 0, [ $cloned_block ] );

		// @phpstan-ignore argument.type
		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $new_index );
		} else {
			WP_CLI::success( "Cloned block to index {$new_index} in post {$post->ID}." );
		}
	}

	/**
	 * Extracts data from blocks.
	 *
	 * Extracts specific attribute values or content from blocks for scripting.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post.
	 *
	 * [--block=<block-name>]
	 * : Filter by block type.
	 *
	 * [--index=<index>]
	 * : Get from specific block index.
	 *
	 * [--attr=<attr>]
	 * : Extract specific attribute value.
	 *
	 * [--content]
	 * : Extract innerHTML content.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - yaml
	 *   - csv
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Extract all image IDs from the post (one per line).
	 *     $ wp post block extract 123 --block=core/image --attr=id --format=ids
	 *     456
	 *     789
	 *     1024
	 *
	 *     # Extract all image URLs as JSON array.
	 *     $ wp post block extract 123 --block=core/image --attr=url --format=json
	 *     ["https://example.com/img1.jpg","https://example.com/img2.jpg"]
	 *
	 *     # Extract text content from all headings.
	 *     $ wp post block extract 123 --block=core/heading --content --format=ids
	 *     Introduction
	 *     Getting Started
	 *     Conclusion
	 *
	 *     # Get the heading level from the first block.
	 *     $ wp post block extract 123 --index=0 --attr=level --format=ids
	 *     2
	 *
	 *     # Extract all heading levels as CSV.
	 *     $ wp post block extract 123 --block=core/heading --attr=level --format=csv
	 *     2,3,3,2
	 *
	 *     # Extract paragraph content as YAML.
	 *     $ wp post block extract 123 --block=core/paragraph --content --format=yaml
	 *     - "First paragraph text"
	 *     - "Second paragraph text"
	 *
	 *     # Get all button URLs for link checking.
	 *     $ wp post block extract 123 --block=core/button --attr=url --format=ids
	 *     https://example.com/signup
	 *     https://example.com/learn-more
	 *
	 *     # Extract cover block image IDs for media audit.
	 *     $ wp post block extract 123 --block=core/cover --attr=id --format=json
	 *
	 * @subcommand extract
	 */
	public function extract( $args, $assoc_args ) {
		$post         = $this->fetcher->get_check( $args[0] );
		$block_filter = Utils\get_flag_value( $assoc_args, 'block', null );
		$index        = Utils\get_flag_value( $assoc_args, 'index', null );
		$attr         = Utils\get_flag_value( $assoc_args, 'attr', null );
		$get_content  = Utils\get_flag_value( $assoc_args, 'content', false );
		$format       = Utils\get_flag_value( $assoc_args, 'format', 'json' );

		if ( null === $attr && ! $get_content ) {
			WP_CLI::error( 'You must specify either --attr or --content.' );
		}

		$blocks = parse_blocks( $post->post_content );

		// Filter out empty blocks.
		$blocks = array_values(
			array_filter(
				$blocks,
				function ( $block ) {
					return ! empty( $block['blockName'] );
				}
			)
		);

		// Filter by index.
		if ( null !== $index ) {
			$index = (int) $index;
			if ( $index < 0 || $index >= count( $blocks ) ) {
				WP_CLI::error( "Invalid index: {$index}. Post has " . count( $blocks ) . ' block(s) (0-indexed).' );
			}
			$blocks = [ $blocks[ $index ] ];
		}

		// Filter by block type.
		if ( null !== $block_filter ) {
			$blocks = array_filter(
				$blocks,
				function ( $block ) use ( $block_filter ) {
					return $block['blockName'] === $block_filter;
				}
			);
		}

		if ( empty( $blocks ) ) {
			WP_CLI::warning( 'No matching blocks found.' );
			return;
		}

		// Extract values.
		$values = [];
		foreach ( $blocks as $block ) {
			if ( $get_content ) {
				$content = isset( $block['innerHTML'] ) ? $block['innerHTML'] : '';
				// Strip HTML tags for cleaner output.
				$values[] = trim( wp_strip_all_tags( $content ) );
			} elseif ( null !== $attr ) {
				if ( isset( $block['attrs'][ $attr ] ) ) {
					$values[] = $block['attrs'][ $attr ];
				}
			}
		}

		if ( empty( $values ) ) {
			WP_CLI::warning( 'No values found for extraction criteria.' );
			return;
		}

		// Output based on format.
		switch ( $format ) {
			case 'ids':
				foreach ( $values as $value ) {
					WP_CLI::line( (string) $value );
				}
				break;
			case 'csv':
				WP_CLI::line( implode( ',', array_map( 'strval', $values ) ) );
				break;
			case 'yaml':
				echo Spyc::YAMLDump( $values, 2, 0, true );
				break;
			case 'json':
			default:
				// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
				echo json_encode( $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
				break;
		}
	}

	/**
	 * Parses and displays the block structure of a post.
	 *
	 * Outputs the parsed block structure as JSON or YAML. By default,
	 * innerHTML is stripped from the output for readability.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to parse.
	 *
	 * [--raw]
	 * : Include raw innerHTML in output.
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
	 *     # Parse blocks to JSON.
	 *     $ wp post block parse 123
	 *     [
	 *         {
	 *             "blockName": "core/paragraph",
	 *             "attrs": {}
	 *         }
	 *     ]
	 *
	 *     # Parse blocks to YAML format.
	 *     $ wp post block parse 123 --format=yaml
	 *     -
	 *       blockName: core/paragraph
	 *       attrs: {  }
	 *
	 *     # Parse blocks including raw HTML content.
	 *     $ wp post block parse 123 --raw
	 *
	 * @subcommand parse
	 */
	public function parse( $args, $assoc_args ) {
		$post   = $this->fetcher->get_check( $args[0] );
		$blocks = parse_blocks( $post->post_content );

		$include_raw = Utils\get_flag_value( $assoc_args, 'raw', false );

		if ( ! $include_raw ) {
			$blocks = $this->strip_inner_html( $blocks );
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'json' );

		if ( 'yaml' === $format ) {
			echo Spyc::YAMLDump( $blocks, 2, 0, true );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
			echo json_encode( $blocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
		}
	}

	/**
	 * Renders blocks from a post to HTML.
	 *
	 * Outputs the rendered HTML of blocks in a post. This uses WordPress's
	 * block rendering system to produce the final HTML output.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to render.
	 *
	 * [--block=<block-name>]
	 * : Only render blocks of this type.
	 *
	 * ## EXAMPLES
	 *
	 *     # Render all blocks to HTML.
	 *     $ wp post block render 123
	 *     <p>Hello World</p>
	 *     <h2>My Heading</h2>
	 *
	 *     # Render only paragraph blocks.
	 *     $ wp post block render 123 --block=core/paragraph
	 *     <p>Hello World</p>
	 *
	 *     # Render only heading blocks.
	 *     $ wp post block render 123 --block=core/heading
	 *
	 * @subcommand render
	 */
	public function render( $args, $assoc_args ) {
		$post        = $this->fetcher->get_check( $args[0] );
		$block_name  = Utils\get_flag_value( $assoc_args, 'block', null );
		$blocks      = parse_blocks( $post->post_content );
		$output_html = '';

		foreach ( $blocks as $block ) {
			if ( null !== $block_name && $block['blockName'] !== $block_name ) {
				continue;
			}
			$output_html .= render_block( $block );
		}

		echo $output_html;
	}

	/**
	 * Lists blocks in a post with counts.
	 *
	 * Displays a summary of block types used in the post and how many
	 * times each block type appears.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to analyze.
	 *
	 * [--nested]
	 * : Include nested/inner blocks in the list.
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
	 *   - count
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List blocks with counts.
	 *     $ wp post block list 123
	 *     +------------------+-------+
	 *     | blockName        | count |
	 *     +------------------+-------+
	 *     | core/paragraph   | 5     |
	 *     | core/heading     | 2     |
	 *     | core/image       | 1     |
	 *     +------------------+-------+
	 *
	 *     # List blocks as JSON.
	 *     $ wp post block list 123 --format=json
	 *     [{"blockName":"core/paragraph","count":5}]
	 *
	 *     # Include nested blocks (e.g., blocks inside columns or groups).
	 *     $ wp post block list 123 --nested
	 *
	 *     # Get the number of unique block types.
	 *     $ wp post block list 123 --format=count
	 *     3
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$post   = $this->fetcher->get_check( $args[0] );
		$blocks = parse_blocks( $post->post_content );

		$include_nested = Utils\get_flag_value( $assoc_args, 'nested', false );

		$block_counts = [];
		$this->count_blocks( $blocks, $block_counts, $include_nested );

		$items = [];
		foreach ( $block_counts as $block_name => $count ) {
			$items[] = [
				'blockName' => $block_name,
				'count'     => $count,
			];
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( 'count' === $format ) {
			WP_CLI::line( (string) count( $items ) );
			return;
		}

		$formatter = new Formatter( $assoc_args, [ 'blockName', 'count' ] );
		$formatter->display_items( $items );
	}

	/**
	 * Inserts a block into a post at a specified position.
	 *
	 * Adds a new block to the post content. By default, the block is
	 * appended to the end of the post.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to modify.
	 *
	 * <block-name>
	 * : The block type name (e.g., 'core/paragraph').
	 *
	 * [--content=<content>]
	 * : The inner content/HTML for the block.
	 *
	 * [--attrs=<attrs>]
	 * : Block attributes as JSON.
	 *
	 * [--position=<position>]
	 * : Position to insert the block (0-indexed). Use 'start' or 'end'.
	 * ---
	 * default: end
	 * ---
	 *
	 * [--porcelain]
	 * : Output just the post ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Insert a paragraph block at the end of the post.
	 *     $ wp post block insert 123 core/paragraph --content="Hello World"
	 *     Success: Inserted block into post 123.
	 *
	 *     # Insert a level-2 heading at the start.
	 *     $ wp post block insert 123 core/heading --content="My Title" --attrs='{"level":2}' --position=start
	 *     Success: Inserted block into post 123.
	 *
	 *     # Insert an image block at position 2.
	 *     $ wp post block insert 123 core/image --attrs='{"id":456,"url":"https://example.com/image.jpg"}' --position=2
	 *
	 *     # Insert a separator block.
	 *     $ wp post block insert 123 core/separator
	 *
	 * @subcommand insert
	 */
	public function insert( $args, $assoc_args ) {
		$post       = $this->fetcher->get_check( $args[0] );
		$block_name = $args[1];
		$content    = Utils\get_flag_value( $assoc_args, 'content', '' );
		$attrs_json = Utils\get_flag_value( $assoc_args, 'attrs', '{}' );
		$position   = Utils\get_flag_value( $assoc_args, 'position', 'end' );

		$attrs = json_decode( $attrs_json, true );
		if ( null === $attrs && '{}' !== $attrs_json ) {
			WP_CLI::error( 'Invalid JSON provided for --attrs.' );
		}
		if ( ! is_array( $attrs ) ) {
			$attrs = [];
		}

		$blocks = parse_blocks( $post->post_content );

		$new_block = $this->create_block( $block_name, $attrs, $content );

		if ( 'start' === $position ) {
			array_unshift( $blocks, $new_block );
		} elseif ( 'end' === $position ) {
			$blocks[] = $new_block;
		} else {
			$pos = (int) $position;
			if ( $pos < 0 || $pos > count( $blocks ) ) {
				WP_CLI::error( "Invalid position: {$position}. Must be between 0 and " . count( $blocks ) . '.' );
			}
			array_splice( $blocks, $pos, 0, [ $new_block ] );
		}

		// @phpstan-ignore argument.type
		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $post->ID );
		} else {
			WP_CLI::success( "Inserted block into post {$post->ID}." );
		}
	}

	/**
	 * Removes blocks from a post by name or index.
	 *
	 * Removes one or more blocks from the post content. Blocks can be
	 * removed by their type name or by their position index.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to modify.
	 *
	 * [<block-name>]
	 * : The block type name to remove (e.g., 'core/paragraph').
	 *
	 * [--index=<index>]
	 * : Remove block at specific index (0-indexed). Can be comma-separated for multiple indices.
	 *
	 * [--all]
	 * : Remove all blocks of the specified type.
	 *
	 * [--porcelain]
	 * : Output just the number of blocks removed.
	 *
	 * ## EXAMPLES
	 *
	 *     # Remove the first block (index 0).
	 *     $ wp post block remove 123 --index=0
	 *     Success: Removed 1 block from post 123.
	 *
	 *     # Remove the first paragraph block found.
	 *     $ wp post block remove 123 core/paragraph
	 *     Success: Removed 1 block from post 123.
	 *
	 *     # Remove all paragraph blocks.
	 *     $ wp post block remove 123 core/paragraph --all
	 *     Success: Removed 5 blocks from post 123.
	 *
	 *     # Remove blocks at multiple indices.
	 *     $ wp post block remove 123 --index=0,2,4
	 *     Success: Removed 3 blocks from post 123.
	 *
	 *     # Remove all image blocks and get count.
	 *     $ wp post block remove 123 core/image --all --porcelain
	 *     2
	 *
	 * @subcommand remove
	 */
	public function remove( $args, $assoc_args ) {
		$post       = $this->fetcher->get_check( $args[0] );
		$block_name = isset( $args[1] ) ? $args[1] : null;
		$indices    = Utils\get_flag_value( $assoc_args, 'index', null );
		$remove_all = Utils\get_flag_value( $assoc_args, 'all', false );

		if ( null === $block_name && null === $indices ) {
			WP_CLI::error( 'You must specify either a block name or --index.' );
		}

		$blocks        = parse_blocks( $post->post_content );
		$removed_count = 0;

		if ( null !== $indices ) {
			$index_array = array_map( 'intval', explode( ',', $indices ) );
			rsort( $index_array );

			foreach ( $index_array as $idx ) {
				if ( isset( $blocks[ $idx ] ) ) {
					array_splice( $blocks, $idx, 1 );
					++$removed_count;
				}
			}
		} elseif ( $remove_all && null !== $block_name ) {
			$new_blocks = [];
			foreach ( $blocks as $block ) {
				if ( $block['blockName'] === $block_name ) {
					++$removed_count;
				} else {
					$new_blocks[] = $block;
				}
			}
			$blocks = $new_blocks;
		} elseif ( null !== $block_name ) {
			foreach ( $blocks as $idx => $block ) {
				if ( $block['blockName'] === $block_name ) {
					array_splice( $blocks, (int) $idx, 1 );
					++$removed_count;
					break;
				}
			}
		}

		if ( 0 === $removed_count ) {
			WP_CLI::warning( 'No blocks were removed.' );
			return;
		}

		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $removed_count );
		} else {
			$block_word = 1 === $removed_count ? 'block' : 'blocks';
			WP_CLI::success( "Removed {$removed_count} {$block_word} from post {$post->ID}." );
		}
	}

	/**
	 * Replaces blocks in a post.
	 *
	 * Replaces blocks of one type with blocks of another type. Can also
	 * be used to update block attributes without changing the block type.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the post to modify.
	 *
	 * <old-block-name>
	 * : The block type name to replace.
	 *
	 * <new-block-name>
	 * : The new block type name.
	 *
	 * [--attrs=<attrs>]
	 * : New block attributes as JSON.
	 *
	 * [--content=<content>]
	 * : New block content. Use '{content}' to preserve original content.
	 *
	 * [--all]
	 * : Replace all matching blocks. By default, only the first match is replaced.
	 *
	 * [--porcelain]
	 * : Output just the number of blocks replaced.
	 *
	 * ## EXAMPLES
	 *
	 *     # Replace the first paragraph block with a heading.
	 *     $ wp post block replace 123 core/paragraph core/heading
	 *     Success: Replaced 1 block in post 123.
	 *
	 *     # Replace all paragraphs with preformatted blocks, keeping content.
	 *     $ wp post block replace 123 core/paragraph core/preformatted --content='{content}' --all
	 *     Success: Replaced 3 blocks in post 123.
	 *
	 *     # Change all h2 headings to h3.
	 *     $ wp post block replace 123 core/heading core/heading --attrs='{"level":3}' --all
	 *
	 *     # Replace and get count for scripting.
	 *     $ wp post block replace 123 core/quote core/pullquote --all --porcelain
	 *     2
	 *
	 * @subcommand replace
	 */
	public function replace( $args, $assoc_args ) {
		$post           = $this->fetcher->get_check( $args[0] );
		$old_block_name = $args[1];
		$new_block_name = $args[2];
		$attrs_json     = Utils\get_flag_value( $assoc_args, 'attrs', null );
		$content        = Utils\get_flag_value( $assoc_args, 'content', null );
		$replace_all    = Utils\get_flag_value( $assoc_args, 'all', false );

		$new_attrs = null;
		if ( null !== $attrs_json ) {
			$new_attrs = json_decode( $attrs_json, true );
			if ( null === $new_attrs ) {
				WP_CLI::error( 'Invalid JSON provided for --attrs.' );
			}
		}

		$blocks         = parse_blocks( $post->post_content );
		$replaced_count = 0;

		foreach ( $blocks as $idx => $block ) {
			if ( $block['blockName'] !== $old_block_name ) {
				continue;
			}

			$block_attrs   = is_array( $new_attrs ) ? $new_attrs : ( is_array( $block['attrs'] ) ? $block['attrs'] : [] );
			$block_content = $content;

			if ( null === $block_content || '{content}' === $block_content ) {
				$block_content = $block['innerHTML'];
			}

			$blocks[ $idx ] = $this->create_block( $new_block_name, $block_attrs, (string) $block_content );
			++$replaced_count;

			if ( ! $replace_all ) {
				break;
			}
		}

		if ( 0 === $replaced_count ) {
			WP_CLI::warning( "No blocks of type '{$old_block_name}' were found." );
			return;
		}

		// @phpstan-ignore argument.type
		$new_content = serialize_blocks( $blocks );
		$result      = wp_update_post(
			[
				'ID'           => $post->ID,
				'post_content' => $new_content,
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $replaced_count );
		} else {
			$block_word = 1 === $replaced_count ? 'block' : 'blocks';
			WP_CLI::success( "Replaced {$replaced_count} {$block_word} in post {$post->ID}." );
		}
	}

	/**
	 * Recursively counts blocks.
	 *
	 * @param array $blocks         Array of blocks to count.
	 * @param array $counts         Reference to counts array.
	 * @param bool  $include_nested Whether to include nested blocks.
	 */
	private function count_blocks( $blocks, &$counts, $include_nested = false ) {
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( ! isset( $counts[ $block['blockName'] ] ) ) {
				$counts[ $block['blockName'] ] = 0;
			}
			++$counts[ $block['blockName'] ];

			if ( $include_nested && ! empty( $block['innerBlocks'] ) ) {
				$this->count_blocks( $block['innerBlocks'], $counts, true );
			}
		}
	}

	/**
	 * Strips innerHTML and innerContent from blocks recursively.
	 *
	 * @param array $blocks Array of blocks.
	 * @return array Blocks with innerHTML stripped.
	 */
	private function strip_inner_html( $blocks ) {
		return array_map(
			function ( $block ) {
				unset( $block['innerHTML'] );
				unset( $block['innerContent'] );
				if ( ! empty( $block['innerBlocks'] ) ) {
					$block['innerBlocks'] = $this->strip_inner_html( $block['innerBlocks'] );
				}
				return $block;
			},
			$blocks
		);
	}

	/**
	 * Creates a block array structure.
	 *
	 * @param string $block_name Block name.
	 * @param array  $attrs      Block attributes.
	 * @param string $content    Block content.
	 * @return array Block structure.
	 */
	private function create_block( $block_name, $attrs, $content = '' ) {
		$inner_html = $content;

		if ( ! empty( $content ) && ! preg_match( '/^</', $content ) ) {
			$inner_html = "<p>{$content}</p>";
		}

		return [
			'blockName'    => $block_name,
			'attrs'        => $attrs ?: [],
			'innerBlocks'  => [],
			'innerHTML'    => $inner_html,
			'innerContent' => [ $inner_html ],
		];
	}

	/**
	 * Aggregates block counts across posts.
	 *
	 * @param array       $blocks       Array of blocks.
	 * @param array       $block_counts Reference to block counts.
	 * @param array       $post_counts  Reference to post counts per block type.
	 * @param int         $post_id      Current post ID.
	 * @param string|null $block_filter Optional filter for specific block type.
	 */
	private function aggregate_block_counts( $blocks, &$block_counts, &$post_counts, $post_id, $block_filter = null ) {
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$block_name = $block['blockName'];

			if ( null !== $block_filter && $block_name !== $block_filter ) {
				// Still recurse into inner blocks in case they match.
				if ( ! empty( $block['innerBlocks'] ) ) {
					$this->aggregate_block_counts( $block['innerBlocks'], $block_counts, $post_counts, $post_id, $block_filter );
				}
				continue;
			}

			if ( ! isset( $block_counts[ $block_name ] ) ) {
				$block_counts[ $block_name ] = 0;
				$post_counts[ $block_name ]  = [];
			}
			++$block_counts[ $block_name ];
			$post_counts[ $block_name ][ $post_id ] = true;

			// Recurse into inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->aggregate_block_counts( $block['innerBlocks'], $block_counts, $post_counts, $post_id, $block_filter );
			}
		}
	}

	/**
	 * Deep copies a block structure.
	 *
	 * @param array $block Block to copy.
	 * @return array Copied block.
	 */
	private function deep_copy_block( $block ) {
		$copy = $block;

		if ( ! empty( $copy['innerBlocks'] ) ) {
			$copy['innerBlocks'] = array_map( [ $this, 'deep_copy_block' ], $copy['innerBlocks'] );
		}

		return $copy;
	}
}
