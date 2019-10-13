<?php

namespace WP_CLI\Entity;

/**
 * A class containing a diff implementation
 *
 * Created by Kate Morley - http://iamkate.com/ - and released under the terms of
 * the CC0 1.0 Universal legal code:
 *
 * http://creativecommons.org/publicdomain/zero/1.0/legalcode
 */

// A class containing functions for computing diffs and formatting the output.
class Diff {

	// Define the constants.
	const UNMODIFIED = 0;
	const DELETED    = 1;
	const INSERTED   = 2;

	/**
	 * Returns the diff for two strings. The return value is an array, each of
	 * whose values is an array containing two values: a line and one of the constants
	 *
	 * DIFF::UNMODIFIED (the line is in both strings),
	 * DIFF::DELETED (the line is only in the first string), and
	 * DIFF::INSERTED (the line is only in the second string).
	 *
	 * @param string $string1 The first string.
	 * @param string $string2 The second string.
	 *
	 * @return array
	 */
	public static function compare( $string1, $string2 ) {
		// Initialise the sequences and comparison start and end positions.
		$start     = 0;
		$sequence1 = preg_split( '/\R/', $string1 );
		$sequence2 = preg_split( '/\R/', $string2 );
		$end1      = count( $sequence1 ) - 1;
		$end2      = count( $sequence2 ) - 1;

		// Skip any common prefix.
		while ( $start <= $end1 && $start <= $end2 && $sequence1[ $start ] == $sequence2[ $start ] ) {
			$start ++;
		}

		// Skip any common suffix.
		while ( $end1 >= $start && $end2 >= $start && $sequence1[ $end1 ] == $sequence2[ $end2 ] ) {
			$end1 --;
			$end2 --;
		}

		// Compute the table of longest common subsequence lengths.
		$table = self::computeTable( $sequence1, $sequence2, $start, $end1, $end2 );

		// Generate the partial diff.
		$partialDiff = self::generatePartialDiff( $table, $sequence1, $sequence2, $start );

		// Generate the full diff.
		$diff = array();
		for ( $index = 0; $index < $start; $index++ ) {
			$diff[] = array( $sequence1[ $index ], self::UNMODIFIED );
		}

		while ( count( $partialDiff ) > 0 ) {
			$diff[] = array_pop( $partialDiff );
		}

		for ( $index = $end1 + 1; $index < count( $sequence1 ); $index ++ ) {
			$diff[] = array( $sequence1[ $index ], self::UNMODIFIED );
		}
		// Return the diff.
		return $diff;
	}

	/*
	 * Returns the table of longest common subsequence lengths for the specified
	 *
	 * @param array $sequence1 The first sequence.
	 * @param array $sequence2 The second sequence.
	 * @param int   $start     The starting index.
	 * @Param int   $end1      The ending index for the first sequence.
	 * @param int   $end2      The ending index for the second sequence.
	 *
	 * @return array
	 */
	private static function computeTable( $sequence1, $sequence2, $start, $end1, $end2 ) {
		// Determine the lengths to be compared.
		$length1 = $end1 - $start + 1;
		$length2 = $end2 - $start + 1;
		// Initialise the table.
		$table = array( array_fill( 0, $length2 + 1, 0 ) );
		// Loop over the rows.
		for ( $index1 = 1; $index1 <= $length1; $index1++ ) {
			// Create the new row.
			$table[ $index1 ] = array( 0 );
			// Loop over the columns.
			for ( $index2 = 1; $index2 <= $length2; $index2++ ) {
				// Store the longest common subsequence length.
				if ( $sequence1[ $index1 + $start - 1 ] == $sequence2[ $index2 + $start - 1 ] ) {
					$table[ $index1 ][ $index2 ] = $table[ $index1 - 1 ][ $index2 - 1 ] + 1;
				} else {
					$table[ $index1 ][ $index2 ] = max( $table[ $index1 - 1 ][ $index2 ], $table[ $index1 ][ $index2 - 1 ] );
				}
			}
		}
		// Return the table.
		return $table;
	}

	/**
	 * Returns the partial diff for the specified sequences, in reverse order.
	 *
	 * @param array $table     The table returned by the computeTable function.
	 * @param array $sequence1 The first sequence.
	 * @param array $sequence2 The second sequence.
	 * @param int   $start     The starting index.
	 *
	 * @return array
	 */
	private static function generatePartialDiff ( $table, $sequence1, $sequence2, $start ) {
		// Initialise the diff.
		$diff = array();
		// Initialise the indices.
		$index1 = count( $table ) - 1;
		$index2 = count( $table[0] ) - 1;
		// Loop until there are no items remaining in either sequence.
		while ($index1 > 0 || $index2 > 0){
			// Check what has happened to the items at these indices.
			if ( $index1 > 0 && $index2 > 0 && $sequence1[ $index1 + $start - 1 ] == $sequence2[ $index2 + $start - 1 ] ) {
				// Update the diff and the indices.
				$diff[] = array( $sequence1[ $index1 + $start - 1 ], self::UNMODIFIED );
				$index1 --;
				$index2 --;
			} elseif ( $index2 > 0 && $table[ $index1 ][ $index2 ] == $table[ $index1 ][ $index2 - 1 ] ) {
				// Update the diff and the indices.
				$diff[] = array( $sequence2[ $index2 + $start - 1 ], self::INSERTED );
				$index2 --;
			} else {
				// Update the diff and the indices.
				$diff[] = array( $sequence1[ $index1 + $start - 1 ], self::DELETED );
				$index1 --;
			}
		}
		// Return the diff.
		return $diff;
	}

	/**
	 * Returns a diff as a string, where unmodified lines are colored by yellow,
	 * deletions are colored by 'Red', and insertions are colored by 'Green'.
	 *
	 * @param array $diff The diff.
	 *
	 * @return string
	 */
	public static function to_string( $diff ) {
		// Initialise the string.
		$string  = '';
		$is_diff = false;
		// Loop over the lines in the diff.
		foreach ( $diff as $line ) {
			// Check case and set colorize line.
			switch ( $line[1] ) {
				case self::UNMODIFIED:
					$string .= \WP_CLI::colorize( '%y' . $line[0] . '%n' );
					break;
				case self::DELETED:
					$string .= \WP_CLI::colorize( '%r' . $line[0] . '%n' );
					$is_diff = true;
					break;
				case self::INSERTED:
					$string .= \WP_CLI::colorize( '%g' . $line[0] . '%n' );
					$is_diff = true;
					break;
			}
			// Extend the string with the separator.
			$string .= "\n";
		}
		// If diff found then return diff.
		if ( $is_diff ) {
			// Return the string.
			return $string;
		}
		// NO diff found.
		return '';
	}
}
