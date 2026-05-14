<?php

namespace WP_CLI;

use WP_CLI;

/**
 * Trait that provides ID range expansion for WP-CLI commands.
 *
 * Allows commands that accept one or more IDs to also accept range notations
 * such as "1-10", "5-", or "-20".
 */
trait ExpandsIdRanges {

	/**
	 * Expands ID arguments that may include ranges into a flat list of IDs.
	 *
	 * Supports the following range notations:
	 * - "15-35"  Full range: all existing IDs from 15 to 35 inclusive.
	 * - "34-"    Open-ended range: all existing IDs from 34 onwards.
	 * - "-35"    Lower-bounded range: all existing IDs from 1 up to 35 inclusive.
	 *
	 * Individual IDs and non-range arguments are passed through unchanged.
	 *
	 * @param array    $args             Raw arguments that may include ranges.
	 * @param callable $get_ids_in_range Callback receiving (int $start, int|null $end) that
	 *                                   returns an array of existing IDs within that range.
	 * @return array Flat list of unique IDs, with range arguments expanded.
	 */
	protected static function expand_id_ranges( array $args, callable $get_ids_in_range ): array {
		$ids = [];

		foreach ( $args as $arg ) {
			if ( preg_match( '/^(\d+)-(\d+)$/', $arg, $matches ) ) {
				// Full range: "15-35"
				$start = (int) $matches[1];
				$end   = (int) $matches[2];
				if ( $start > $end ) {
					// Normalize reversed ranges like "35-15" to "15-35".
					$temp  = $start;
					$start = $end;
					$end   = $temp;
				}
				$range_ids = $get_ids_in_range( $start, $end );
				WP_CLI::debug( sprintf( "Expanded range '%s' to %d IDs.", $arg, count( $range_ids ) ), 'range-expansion' );
				$ids = array_merge( $ids, $range_ids );
			} elseif ( preg_match( '/^(\d+)-$/', $arg, $matches ) ) {
				// Open-ended range: "34-"
				$range_ids = $get_ids_in_range( (int) $matches[1], null );
				WP_CLI::debug( sprintf( "Expanded range '%s' to %d IDs.", $arg, count( $range_ids ) ), 'range-expansion' );
				$ids = array_merge( $ids, $range_ids );
			} elseif ( preg_match( '/^-(\d+)$/', $arg, $matches ) ) {
				// Lower-bounded range: "-35"
				$range_ids = $get_ids_in_range( 1, (int) $matches[1] );
				WP_CLI::debug( sprintf( "Expanded range '%s' to %d IDs.", $arg, count( $range_ids ) ), 'range-expansion' );
				$ids = array_merge( $ids, $range_ids );
			} else {
				$ids[] = $arg;
			}
		}

		return array_values( array_unique( $ids ) );
	}
}
