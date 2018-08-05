<?php

namespace WP_CLI\Entity;

class Utils {

	/**
	 * Check whether any input is passed to STDIN.
	 *
	 * @return bool
	 */
	public static function has_stdin() {
		$handle  = fopen( 'php://stdin', 'r' );
		$read    = array( $handle );
		$write   = null;
		$except  = null;
		$streams = stream_select( $read, $write, $except, 0 );
		$fstat   = fstat( $handle );

		fclose( $handle );

		if ( $streams !== 1 ) {
			// No stream on STDIN.
			return false;
		}

		if ( isset( $fstat['size'] ) && $fstat['size'] === 0 ) {
			// A stream on STDIN was detected but has a size of 0 bytes.
			return false;
		}

		return true;
	}
}
