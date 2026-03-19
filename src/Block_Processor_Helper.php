<?php
/**
 * Helper class for streaming block processing.
 *
 * Provides common streaming patterns for working with WP_Block_Processor.
 * This class abstracts the low-level WP_Block_Processor API into higher-level
 * operations that can be used by commands.
 *
 * @package WP_CLI\Entity
 */

namespace WP_CLI\Entity;

use WP_Block_Processor;
use WP_CLI\Entity\Compat\BlockProcessorLoader;

/**
 * Helper class for streaming block processing operations.
 *
 * This class provides static methods that encapsulate common patterns
 * for working with blocks using the WP_Block_Processor streaming API.
 * All methods work consistently across WordPress versions by using the
 * polyfilled or native WP_Block_Processor.
 */
class Block_Processor_Helper {

	/**
	 * Ensures the WP_Block_Processor class is available.
	 *
	 * This method loads the polyfill if needed. It's called at the start
	 * of each public method to ensure the dependency is available.
	 *
	 * @return void
	 */
	private static function ensure_loaded(): void {
		BlockProcessorLoader::load();
	}

	/**
	 * Parses all blocks from content using streaming processor.
	 *
	 * This method provides an alternative to parse_blocks() that uses
	 * WP_Block_Processor for consistent behavior across WP versions.
	 *
	 * @param string $content Block content to parse.
	 * @return array Array of parsed block structures.
	 */
	public static function parse_all( string $content ): array {
		if ( '' === $content ) {
			return [];
		}

		self::ensure_loaded();
		$processor = new WP_Block_Processor( $content );
		$blocks    = [];

		while ( $processor->next_block() ) {
			// Only process top-level blocks (depth 1).
			if ( 1 !== $processor->get_depth() ) {
				continue;
			}

			$delimiter_type = $processor->get_delimiter_type();

			// Handle void blocks specially - don't use extract which causes issues.
			if ( WP_Block_Processor::VOID === $delimiter_type ) {
				$blocks[] = [
					'blockName'    => $processor->get_block_type(),
					'attrs'        => $processor->allocate_and_return_parsed_attributes() ?? [],
					'innerBlocks'  => [],
					'innerHTML'    => '',
					'innerContent' => [],
				];
			} elseif ( WP_Block_Processor::OPENER === $delimiter_type ) {
				// For opener blocks, extract_full_block_and_advance works correctly.
				$block = $processor->extract_full_block_and_advance();
				if ( null !== $block ) {
					$blocks[] = $block;
				}
			}
		}

		return $blocks;
	}

	/**
	 * Gets a block at a specific index.
	 *
	 * Uses streaming to find the block without parsing the entire document.
	 *
	 * @param string $content      Block content to search.
	 * @param int    $target_index The 0-based index of the block to get.
	 * @param bool   $skip_freeform Whether to skip freeform HTML blocks (default true).
	 * @return array|null The block structure or null if not found.
	 */
	public static function get_at_index( string $content, int $target_index, bool $skip_freeform = true ): ?array {
		if ( '' === $content || $target_index < 0 ) {
			return null;
		}

		self::ensure_loaded();
		$processor     = new WP_Block_Processor( $content );
		$current_index = 0;

		while ( $processor->next_block() ) {
			// Only consider top-level blocks (depth 1).
			if ( 1 !== $processor->get_depth() ) {
				continue;
			}

			// Skip freeform content unless requested.
			if ( null === $processor->get_block_type() ) {
				if ( $skip_freeform ) {
					continue;
				}
			}

			if ( $current_index === $target_index ) {
				$delimiter_type = $processor->get_delimiter_type();

				// Handle void blocks specially.
				if ( WP_Block_Processor::VOID === $delimiter_type ) {
					return [
						'blockName'    => $processor->get_block_type(),
						'attrs'        => $processor->allocate_and_return_parsed_attributes() ?? [],
						'innerBlocks'  => [],
						'innerHTML'    => '',
						'innerContent' => [],
					];
				}

				return $processor->extract_full_block_and_advance();
			}

			++$current_index;
		}

		return null;
	}

	/**
	 * Counts blocks by type using streaming.
	 *
	 * @param string $content Block content to analyze.
	 * @param bool   $nested  Whether to include nested blocks in count.
	 * @return array Associative array of block type => count.
	 */
	public static function count_by_type( string $content, bool $nested = false ): array {
		if ( '' === $content ) {
			return [];
		}

		self::ensure_loaded();
		$processor = new WP_Block_Processor( $content );
		$counts    = [];

		while ( $processor->next_block() ) {
			$block_type = $processor->get_block_type();

			// Skip freeform HTML.
			if ( null === $block_type ) {
				continue;
			}

			// If not counting nested, only count top-level blocks.
			if ( ! $nested && $processor->get_depth() > 1 ) {
				continue;
			}

			if ( ! isset( $counts[ $block_type ] ) ) {
				$counts[ $block_type ] = 0;
			}

			++$counts[ $block_type ];
		}

		return $counts;
	}

	/**
	 * Checks if a specific block type exists in content.
	 *
	 * Uses streaming for early exit on first match.
	 *
	 * @param string $content    Block content to search.
	 * @param string $block_type Block type to find (e.g., 'core/paragraph' or 'paragraph').
	 * @return bool True if block type exists, false otherwise.
	 */
	public static function has_block( string $content, string $block_type ): bool {
		if ( '' === $content ) {
			return false;
		}

		self::ensure_loaded();
		$processor = new WP_Block_Processor( $content );

		// Use the processor's built-in type filtering for efficiency.
		return $processor->next_block( $block_type );
	}

	/**
	 * Gets the total count of blocks in content.
	 *
	 * @param string $content     Block content to count.
	 * @param bool   $nested      Whether to include nested blocks.
	 * @param bool   $skip_freeform Whether to skip freeform HTML blocks.
	 * @return int Total number of blocks.
	 */
	public static function get_block_count( string $content, bool $nested = false, bool $skip_freeform = true ): int {
		if ( '' === $content ) {
			return 0;
		}

		self::ensure_loaded();
		$processor = new WP_Block_Processor( $content );
		$count     = 0;

		while ( $processor->next_block() ) {
			$block_type = $processor->get_block_type();

			// Skip freeform HTML if requested.
			if ( $skip_freeform && null === $block_type ) {
				continue;
			}

			// If not counting nested, only count top-level blocks.
			if ( ! $nested && $processor->get_depth() > 1 ) {
				continue;
			}

			++$count;
		}

		return $count;
	}

	/**
	 * Extracts blocks matching a filter condition.
	 *
	 * @param string   $content   Block content to search.
	 * @param callable $predicate Function that receives block type and attributes, returns bool.
	 * @param int      $limit     Maximum number of blocks to return (0 = unlimited).
	 * @return array Array of matching block structures.
	 */
	public static function extract_matching( string $content, callable $predicate, int $limit = 0 ): array {
		if ( '' === $content ) {
			return [];
		}

		self::ensure_loaded();
		$processor = new WP_Block_Processor( $content );
		$blocks    = [];

		while ( $processor->next_block() ) {
			$block_type = $processor->get_block_type();

			// Skip freeform content for matching.
			if ( null === $block_type ) {
				continue;
			}

			// Only check top-level blocks.
			if ( $processor->get_depth() > 1 ) {
				continue;
			}

			$attrs = $processor->allocate_and_return_parsed_attributes() ?? [];

			if ( $predicate( $block_type, $attrs ) ) {
				$delimiter_type = $processor->get_delimiter_type();

				// Handle void blocks specially.
				if ( WP_Block_Processor::VOID === $delimiter_type ) {
					$block = [
						'blockName'    => $block_type,
						'attrs'        => $attrs,
						'innerBlocks'  => [],
						'innerHTML'    => '',
						'innerContent' => [],
					];
				} else {
					$block = $processor->extract_full_block_and_advance();
				}

				if ( null !== $block ) {
					$blocks[] = $block;

					if ( $limit > 0 && count( $blocks ) >= $limit ) {
						break;
					}
				}
			}
		}

		return $blocks;
	}

	/**
	 * Gets block span (position) information by index.
	 *
	 * Returns the byte offset and length of a block at a given index.
	 * Useful for string splice operations.
	 *
	 * @param string $content      Block content to search.
	 * @param int    $target_index The 0-based index of the block.
	 * @return array|null Array with 'start' and 'end' keys, or null if not found.
	 */
	public static function get_block_span( string $content, int $target_index ): ?array {
		if ( '' === $content || $target_index < 0 ) {
			return null;
		}

		self::ensure_loaded();
		$processor     = new WP_Block_Processor( $content );
		$current_index = 0;

		while ( $processor->next_block() ) {
			$block_type = $processor->get_block_type();

			// Skip freeform content.
			if ( null === $block_type ) {
				continue;
			}

			// Only consider top-level blocks.
			if ( $processor->get_depth() > 1 ) {
				continue;
			}

			if ( $current_index === $target_index ) {
				$start_span = $processor->get_span();
				if ( null === $start_span ) {
					return null;
				}

				$start          = $start_span->start;
				$delimiter_type = $processor->get_delimiter_type();

				// For void blocks, the span is just the delimiter.
				if ( WP_Block_Processor::VOID === $delimiter_type ) {
					return [
						'start' => $start,
						'end'   => $start + $start_span->length,
					];
				}

				// For opener blocks, extract to find the end.
				$processor->extract_full_block_and_advance();

				// After extract, we're positioned at the closer (or next token).
				// The span now points to the closer delimiter.
				$end_span = $processor->get_span();
				if ( null !== $end_span ) {
					// End position is after the closer delimiter.
					$end = $end_span->start + $end_span->length;
				} else {
					$end = strlen( $content );
				}

				return [
					'start' => $start,
					'end'   => $end,
				];
			}

			++$current_index;
		}

		return null;
	}

	/**
	 * Lists all block types present in content.
	 *
	 * Returns a simple array of unique block type names found.
	 *
	 * @param string $content Block content to analyze.
	 * @param bool   $nested  Whether to include nested block types.
	 * @return array Array of unique block type names.
	 */
	public static function get_block_types( string $content, bool $nested = false ): array {
		$counts = self::count_by_type( $content, $nested );
		return array_keys( $counts );
	}

	/**
	 * Checks if content contains any blocks.
	 *
	 * @param string $content Block content to check.
	 * @return bool True if content contains at least one block.
	 */
	public static function has_blocks( string $content ): bool {
		if ( '' === $content ) {
			return false;
		}

		self::ensure_loaded();
		$processor = new WP_Block_Processor( $content );

		while ( $processor->next_block() ) {
			// Skip freeform HTML.
			if ( null !== $processor->get_block_type() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Strips innerHTML and innerContent from blocks recursively.
	 *
	 * @param array $blocks Array of blocks.
	 * @return array Blocks with innerHTML stripped.
	 */
	public static function strip_inner_html( array $blocks ): array {
		return array_map(
			function ( $block ) {
				unset( $block['innerHTML'] );
				unset( $block['innerContent'] );
				if ( ! empty( $block['innerBlocks'] ) ) {
					$block['innerBlocks'] = self::strip_inner_html( $block['innerBlocks'] );
				}
				return $block;
			},
			$blocks
		);
	}

	/**
	 * Filters blocks to only those with non-null blockName.
	 *
	 * Removes freeform/whitespace blocks from array.
	 *
	 * @param array $blocks Array of blocks.
	 * @return array Filtered blocks with re-indexed keys.
	 */
	public static function filter_empty_blocks( array $blocks ): array {
		return array_values(
			array_filter(
				$blocks,
				function ( $block ) {
					return ! empty( $block['blockName'] );
				}
			)
		);
	}
}
