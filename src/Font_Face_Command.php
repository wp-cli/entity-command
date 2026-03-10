<?php

use WP_CLI\Utils;

/**
 * Manages font faces.
 *
 * To list, get, create, update or delete font faces, use `wp post` with
 * `--post_type=wp_font_face`.
 *
 * ## EXAMPLES
 *
 *     # Install a font face for an existing family
 *     $ wp font face install 42 --src="https://example.com/font.woff2" --font-weight=700
 *     Success: Created font face 43.
 *
 *     # List installed font faces
 *     $ wp post list --post_type=wp_font_face
 *
 * @package wp-cli
 */
class Font_Face_Command extends WP_CLI_Command {

	/**
	 * Installs a font face.
	 *
	 * Creates a new font face post with the specified settings.
	 *
	 * ## OPTIONS
	 *
	 * <family-id>
	 * : Font family ID.
	 *
	 * --src=<src>
	 * : Font face source URL or file path.
	 *
	 * [--font-family=<family>]
	 * : CSS font-family value.
	 *
	 * [--font-style=<style>]
	 * : CSS font-style value (e.g., normal, italic).
	 * ---
	 * default: normal
	 * ---
	 *
	 * [--font-weight=<weight>]
	 * : CSS font-weight value (e.g., 400, 700).
	 * ---
	 * default: 400
	 * ---
	 *
	 * [--font-display=<display>]
	 * : CSS font-display value.
	 *
	 * [--porcelain]
	 * : Output just the new font face ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Install a font face
	 *     $ wp font face install 42 --src="https://example.com/font.woff2" --font-weight=700 --font-style=normal
	 *     Success: Created font face 43.
	 *
	 *     # Install a font face with porcelain output
	 *     $ wp font face install 42 --src="font.woff2" --porcelain
	 *     44
	 */
	public function install( $args, $assoc_args ) {
		$family_id = $args[0];

		// Verify parent font family exists.
		$parent_post = get_post( $family_id );
		if ( ! $parent_post || 'wp_font_family' !== $parent_post->post_type ) {
			WP_CLI::error( "Font family {$family_id} doesn't exist." );
		}

		if ( ! isset( $assoc_args['src'] ) ) {
			WP_CLI::error( 'The --src parameter is required.' );
		}

		// Prepare font face settings.
		$face_settings = [
			'src' => $assoc_args['src'],
		];

		if ( isset( $assoc_args['font-family'] ) ) {
			$face_settings['fontFamily'] = $assoc_args['font-family'];
		}

		$font_style                 = isset( $assoc_args['font-style'] ) ? $assoc_args['font-style'] : 'normal';
		$face_settings['fontStyle'] = $font_style;

		$font_weight                 = isset( $assoc_args['font-weight'] ) ? $assoc_args['font-weight'] : '400';
		$face_settings['fontWeight'] = $font_weight;

		if ( isset( $assoc_args['font-display'] ) ) {
			$face_settings['fontDisplay'] = $assoc_args['font-display'];
		}

		// Generate title.
		$title_parts = [ $font_weight, $font_style ];
		$title       = implode( ' ', $title_parts );

		$post_data = [
			'post_type'    => 'wp_font_face',
			'post_parent'  => $family_id,
			'post_title'   => $title,
			'post_status'  => 'publish',
			'post_content' => wp_json_encode( $face_settings ) ?: '{}',
		];

		$font_face_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $font_face_id ) ) {
			WP_CLI::error( $font_face_id );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $font_face_id );
		} else {
			WP_CLI::success( "Created font face {$font_face_id}." );
		}
	}
}
