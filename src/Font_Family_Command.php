<?php

use WP_CLI\Utils;

/**
 * Manages font families.
 *
 * To list, get, create, update or delete font families, use `wp post` with
 * `--post_type=wp_font_family`.
 *
 * ## EXAMPLES
 *
 *     # Install a font family from a collection
 *     $ wp font family install google-fonts inter
 *     Success: Installed font family "Inter" (ID: 42) with 9 font faces.
 *
 *     # List installed font families
 *     $ wp post list --post_type=wp_font_family
 *
 * @package wp-cli
 */
class Font_Family_Command extends WP_CLI_Command {

	/**
	 * Installs a font family from a collection.
	 *
	 * Retrieves a font family from a collection and creates the wp_font_family post
	 * along with all associated font faces.
	 *
	 * ## OPTIONS
	 *
	 * <collection>
	 * : Font collection slug.
	 *
	 * <family>
	 * : Font family slug from the collection.
	 *
	 * [--porcelain]
	 * : Output just the new font family ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Install a font family from a collection
	 *     $ wp font family install google-fonts inter
	 *     Success: Installed font family "Inter" (ID: 42) with 9 font faces.
	 *
	 *     # Install and get the family ID
	 *     $ wp font family install google-fonts roboto --porcelain
	 *     43
	 */
	public function install( $args, $assoc_args ) {
		$collection_slug = $args[0];
		$family_slug     = $args[1];

		// Get the collection.
		$font_library = WP_Font_Library::get_instance();
		$collection   = $font_library->get_font_collection( $collection_slug );

		if ( ! $collection ) {
			WP_CLI::error( "Font collection {$collection_slug} doesn't exist." );
		}

		$collection_data = $collection->get_data();

		if ( is_wp_error( $collection_data ) ) {
			WP_CLI::error( $collection_data );
		}

		$font_families = isset( $collection_data['font_families'] ) ? $collection_data['font_families'] : [];

		// Find the font family in the collection.
		$family_data = null;
		foreach ( $font_families as $family ) {
			if ( isset( $family['font_family_settings']['slug'] ) && $family['font_family_settings']['slug'] === $family_slug ) {
				$family_data = $family['font_family_settings'];
				break;
			}
		}

		if ( ! $family_data ) {
			WP_CLI::error( "Font family '{$family_slug}' not found in collection '{$collection_slug}'." );
		}

		// Prepare font family post data.
		$font_family_settings = [];
		if ( isset( $family_data['fontFamily'] ) ) {
			$font_family_settings['fontFamily'] = $family_data['fontFamily'];
		}
		if ( isset( $family_data['preview'] ) ) {
			$font_family_settings['preview'] = $family_data['preview'];
		}
		if ( isset( $family_data['slug'] ) ) {
			$font_family_settings['slug'] = $family_data['slug'];
		}

		$post_data = [
			'post_type'    => 'wp_font_family',
			'post_title'   => isset( $family_data['name'] ) ? $family_data['name'] : $family_slug,
			'post_name'    => $family_slug,
			'post_status'  => 'publish',
			'post_content' => wp_json_encode( $font_family_settings ) ?: '{}',
		];

		$font_family_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $font_family_id ) ) {
			WP_CLI::error( $font_family_id );
		}

		// Install font faces.
		$face_count  = 0;
		$font_faces  = $family_data['fontFace'] ?? [];
		$face_errors = 0;

		foreach ( $font_faces as $face_data ) {
			$face_settings = [];

			// Copy over relevant settings.
			$settings_to_copy = [ 'fontFamily', 'fontStyle', 'fontWeight', 'src', 'fontDisplay' ];
			foreach ( $settings_to_copy as $setting ) {
				if ( isset( $face_data[ $setting ] ) ) {
					$face_settings[ $setting ] = $face_data[ $setting ];
				}
			}

			// Generate a title for the font face.
			$face_title_parts = [];
			if ( isset( $face_data['fontWeight'] ) ) {
				$face_title_parts[] = $face_data['fontWeight'];
			}
			if ( isset( $face_data['fontStyle'] ) ) {
				$face_title_parts[] = $face_data['fontStyle'];
			}
			$face_title = ! empty( $face_title_parts ) ? implode( ' ', $face_title_parts ) : 'Regular';

			$face_post_data = [
				'post_type'    => 'wp_font_face',
				'post_parent'  => $font_family_id,
				'post_title'   => $face_title,
				'post_status'  => 'publish',
				'post_content' => wp_json_encode( $face_settings ) ?: '{}',
			];

			$face_id = wp_insert_post( $face_post_data, true );

			if ( is_wp_error( $face_id ) ) {
				++$face_errors;
			} else {
				++$face_count;
			}
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $font_family_id );
		} else {
			$family_name = $post_data['post_title'];
			if ( $face_errors > 0 ) {
				WP_CLI::warning( "Installed font family \"{$family_name}\" (ID: {$font_family_id}) with {$face_count} font faces. {$face_errors} font faces failed." );
			} else {
				WP_CLI::success( "Installed font family \"{$family_name}\" (ID: {$font_family_id}) with {$face_count} font faces." );
			}
		}
	}
}
