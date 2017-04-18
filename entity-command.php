<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

$autoload = dirname( __FILE__ ) . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

WP_CLI::add_command( 'comment', 'Comment_Command' );
WP_CLI::add_command( 'comment meta', 'Comment_Meta_Command' );
WP_CLI::add_command( 'menu', 'Menu_Command' );
WP_CLI::add_command( 'menu item', 'Menu_Item_Command' );
WP_CLI::add_command( 'menu location', 'Menu_Location_Command' );
WP_CLI::add_command( 'network meta', 'Network_Meta_Command', array(
	'before_invoke' => function () {
		if ( !is_multisite() ) {
			WP_CLI::error( 'This is not a multisite install.' );
		}
	}
) );
WP_CLI::add_command( 'option', 'Option_Command' );
WP_CLI::add_command( 'post', 'Post_Command' );
WP_CLI::add_command( 'post meta', 'Post_Meta_Command' );
WP_CLI::add_command( 'post term', 'Post_Term_Command' );
WP_CLI::add_command( 'post-type', 'Post_Type_Command' );
WP_CLI::add_command( 'site', 'Site_Command' );
WP_CLI::add_command( 'site option', 'Site_Option_Command', array(
	'before_invoke' => function() {
		if ( !is_multisite() ) {
			WP_CLI::error( 'This is not a multisite install.' );
		}
	}
) );
WP_CLI::add_command( 'taxonomy', 'Taxonomy_Command' );
WP_CLI::add_command( 'term', 'Term_Command' );
WP_CLI::add_command( 'term meta', 'Term_Meta_Command', array(
	'before_invoke' => function() {
		if ( \WP_CLI\Utils\wp_version_compare( '4.4', '<' ) ) {
			WP_CLI::error( "Requires WordPress 4.4 or greater." );
		}
	})
);
WP_CLI::add_command( 'user', 'User_Command' );
WP_CLI::add_command( 'user meta', 'User_Meta_Command' );
WP_CLI::add_command( 'user session', 'User_Session_Command', array(
	'before_invoke' => function() {
		if ( \WP_CLI\Utils\wp_version_compare( '4.0', '<' ) ) {
			WP_CLI::error( "Requires WordPress 4.0 or greater." );
		}
	})
);

WP_CLI::add_command( 'user term', 'User_Term_Command' );
