<?php

use WP_CLI\CommandWithDBObject;
use WP_CLI\ExitException;
use WP_CLI\Fetchers\Site as SiteFetcher;
use WP_CLI\Utils;
use WP_CLI\Formatter;
use WP_CLI\Fetchers\User as UserFetcher;

/**
 * Creates, deletes, empties, moderates, and lists one or more sites on a multisite installation.
 *
 * ## EXAMPLES
 *
 *     # Create site
 *     $ wp site create --slug=example
 *     Success: Site 3 created: www.example.com/example/
 *
 *     # Output a simple list of site URLs
 *     $ wp site list --field=url
 *     http://www.example.com/
 *     http://www.example.com/subdir/
 *
 *     # Delete site
 *     $ wp site delete 123
 *     Are you sure you want to delete the 'http://www.example.com/example' site? [y/n] y
 *     Success: The site at 'http://www.example.com/example' was deleted.
 *
 * @package wp-cli
 *
 * @phpstan-type UserSite object{userblog_id: int, blogname: string, domain: string, path: string, site_id: int, siteurl: string, archived: int, spam: int, deleted: int}
 */
class Site_Command extends CommandWithDBObject {

	protected $obj_type   = 'site';
	protected $obj_id_key = 'blog_id';

	private $fetcher;

	public function __construct() {
		$this->fetcher = new SiteFetcher();
	}

	/**
	 * Delete comments.
	 */
	private function empty_comments() {
		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE $wpdb->comments" );
		$wpdb->query( "TRUNCATE TABLE $wpdb->commentmeta" );
	}

	/**
	 * Delete all posts.
	 */
	private function empty_posts() {
		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE $wpdb->posts" );
		$wpdb->query( "TRUNCATE TABLE $wpdb->postmeta" );
	}

	/**
	 * Delete terms, taxonomies, and tax relationships.
	 */
	private function empty_taxonomies() {
		/**
		 * @var \wpdb $wpdb
		 */
		global $wpdb;

		$taxonomies = $wpdb->get_col( "SELECT DISTINCT taxonomy FROM $wpdb->term_taxonomy" );
		if ( ! empty( $taxonomies ) ) {
			$option_names = array_map(
				static function ( $taxonomy ) {
					return "{$taxonomy}_children";
				},
				$taxonomies
			);
			$placeholders = implode( ', ', array_fill( 0, count( $option_names ), '%s' ) );
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			// @phpstan-ignore argument.type
			$query = $wpdb->prepare( 'DELETE FROM ' . $wpdb->options . ' WHERE option_name IN ( ' . $placeholders . ' )', $option_names );
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			if ( $query ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is already prepared above.
				$wpdb->query( $query );
			}
		}

		$wpdb->query( "TRUNCATE TABLE $wpdb->terms" );
		$wpdb->query( "TRUNCATE TABLE $wpdb->term_taxonomy" );
		$wpdb->query( "TRUNCATE TABLE $wpdb->term_relationships" );
		if ( ! empty( $wpdb->termmeta ) ) {
			$wpdb->query( "TRUNCATE TABLE $wpdb->termmeta" );
		}
	}

	/**
	 * Delete all links by truncating the links table.
	 */
	private function empty_links() {
		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE {$wpdb->links}" );
	}

	/**
	 * Insert default terms.
	 */
	private function insert_default_terms() {
		global $wpdb;

		// Default category
		$cat_name = __( 'Uncategorized' );

		/* translators: Default category slug */
		$cat_slug = sanitize_title( _x( 'Uncategorized', 'Default category slug' ) );

		// @phpstan-ignore function.deprecated
		if ( global_terms_enabled() ) { // phpcs:ignore WordPress.WP.DeprecatedFunctions.global_terms_enabledFound -- Required for backwards compatibility.
			$cat_id = $wpdb->get_var( $wpdb->prepare( "SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = %s", $cat_slug ) );
			if ( null === $cat_id ) {
				$wpdb->insert(
					$wpdb->sitecategories,
					[
						'cat_ID'            => 0,
						'cat_name'          => $cat_name,
						'category_nicename' => $cat_slug,
						'last_updated'      => current_time(
							'mysql',
							true
						),
					]
				);
				$cat_id = $wpdb->insert_id;
			}
			update_option( 'default_category', $cat_id );
		} else {
			$cat_id = 1;
		}

		$wpdb->insert(
			$wpdb->terms,
			[
				'term_id'    => $cat_id,
				'name'       => $cat_name,
				'slug'       => $cat_slug,
				'term_group' => 0,
			]
		);
		$wpdb->insert(
			$wpdb->term_taxonomy,
			[
				'term_id'     => $cat_id,
				'taxonomy'    => 'category',
				'description' => '',
				'parent'      => 0,
				'count'       => 0,
			]
		);
	}

	/**
	 * Reset option values to default.
	 */
	private function reset_options() {
		// Reset Privacy Policy value to prevent error.
		update_option( 'wp_page_for_privacy_policy', 0 );

		// Reset sticky posts option.
		update_option( 'sticky_posts', [] );
	}

	/**
	 * Empties a site of its content (posts, comments, terms, links, and meta).
	 *
	 * Truncates posts, comments, terms, and links tables to empty a site of its
	 * content. Doesn't affect site configuration (options) or users.
	 *
	 * Flushes the object cache after emptying the site to ensure stale data
	 * is not served. On a Multisite installation, this will flush the cache
	 * for all sites.
	 *
	 * To also empty custom database tables, you'll need to hook into command
	 * execution:
	 *
	 * ```
	 * WP_CLI::add_hook( 'after_invoke:site empty', function(){
	 *     global $wpdb;
	 *     foreach( array( 'p2p', 'p2pmeta' ) as $table ) {
	 *         $table = $wpdb->$table;
	 *         $wpdb->query( "TRUNCATE $table" );
	 *     }
	 * });
	 * ```
	 *
	 * ## OPTIONS
	 *
	 * [--uploads]
	 * : Also delete *all* files in the site's uploads directory.
	 *
	 * [--yes]
	 * : Proceed to empty the site without a confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site empty
	 *     Are you sure you want to empty the site at http://www.example.com of all posts, links, comments, and terms? [y/n] y
	 *     Success: The site at 'http://www.example.com' was emptied.
	 *
	 * @subcommand empty
	 */
	public function empty_( $args, $assoc_args ) {

		$upload_message = '';
		if ( Utils\get_flag_value( $assoc_args, 'uploads' ) ) {
			$upload_message = ', and delete its uploads directory';
		}

		WP_CLI::confirm( "Are you sure you want to empty the site at '" . site_url() . "' of all posts, links, comments, and terms" . $upload_message . '?', $assoc_args );

		$this->empty_posts();
		$this->empty_links();
		$this->empty_comments();
		$this->empty_taxonomies();
		$this->insert_default_terms();
		$this->reset_options();
		wp_cache_flush();

		if ( ! empty( $upload_message ) ) {
			$upload_dir = wp_upload_dir();
			$files      = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $upload_dir['basedir'], RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			$files_to_unlink       = [];
			$directories_to_delete = [];
			$is_main_site          = is_main_site();

			/**
			 * @var \SplFileInfo $fileinfo
			 */
			foreach ( $files as $fileinfo ) {
				$realpath = $fileinfo->getRealPath();
				// Don't clobber subsites when operating on the main site
				$normalized_realpath = str_replace( '\\', '/', $realpath );
				if ( $is_main_site && false !== stripos( $normalized_realpath, '/sites/' ) ) {
					continue;
				}
				if ( $fileinfo->isDir() ) {
					$directories_to_delete[] = $realpath;
				} else {
					$files_to_unlink[] = $realpath;
				}
			}
			foreach ( $files_to_unlink as $file ) {
				unlink( $file );
			}
			foreach ( $directories_to_delete as $directory ) {
				// Directory could be main sites directory '/sites' which may be non-empty.
				@rmdir( $directory );
			}
			// May be non-empty if '/sites' still around.
			@rmdir( $upload_dir['basedir'] );
		}

		WP_CLI::success( "The site at '" . site_url() . "' was emptied." );
	}

	/**
	 * Deletes a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [<site-id>]
	 * : The id of the site to delete. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be deleted. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * [--keep-tables]
	 * : Delete the blog from the list, but don't drop its tables.
	 *
	 * [--delete-tables-with-prefix]
	 * : Delete all tables with the site's database table prefix after deleting the site.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site delete 123
	 *     Are you sure you want to delete the http://www.example.com/example site? [y/n] y
	 *     Success: The site at 'http://www.example.com/example' was deleted.
	 */
	public function delete( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		if ( Utils\get_flag_value( $assoc_args, 'keep-tables' ) && Utils\get_flag_value( $assoc_args, 'delete-tables-with-prefix' ) ) {
			WP_CLI::error( "The '--keep-tables' and '--delete-tables-with-prefix' flags cannot be used together." );
		}

		if ( isset( $assoc_args['slug'] ) ) {
			$blog_id = get_id_from_blogname( $assoc_args['slug'] );
			if ( null === $blog_id ) {
				WP_CLI::error( sprintf( 'Could not find site with slug \'%s\'.', $assoc_args['slug'] ) );
			}
			if ( is_main_site( $blog_id ) ) {
				WP_CLI::error( 'You cannot delete the root site.' );
			}
			$blog = get_blog_details( $blog_id );
		} else {
			if ( empty( $args ) ) {
				WP_CLI::error( 'Need to specify a blog id.' );
			}

			$blog_id = $args[0];

			if ( is_main_site( $blog_id ) ) {
				WP_CLI::error( 'You cannot delete the root site.' );
			}

			$blog = get_blog_details( $blog_id );
		}

		if ( ! $blog ) {
			WP_CLI::error( 'Site not found.' );
		}

		$site_url = trailingslashit( $blog->siteurl );

		WP_CLI::confirm( "Are you sure you want to delete the '{$site_url}' site?", $assoc_args );

		$did_delete = wpmu_delete_blog( (int) $blog->blog_id, ! Utils\get_flag_value( $assoc_args, 'keep-tables' ) );
		if ( false === $did_delete ) {
			WP_CLI::error( "The site at '{$site_url}' could not be deleted." );
		}

		if ( Utils\get_flag_value( $assoc_args, 'delete-tables-with-prefix' ) ) {
			$this->drop_tables_with_prefix( (int) $blog->blog_id );
		}

		WP_CLI::success( "The site at '{$site_url}' was deleted." );
	}

	/**
	 * Drops all database tables for a site prefix.
	 *
	 * @param int $blog_id Site ID.
	 */
	private function drop_tables_with_prefix( $blog_id ) {
		global $wpdb;

		$blog_prefix = $wpdb->get_blog_prefix( $blog_id );
		if ( is_main_site( $blog_id ) || $blog_prefix === $wpdb->base_prefix ) {
			WP_CLI::error( 'You cannot drop tables for the root site.' );
		}

		$prefix_like = $wpdb->esc_like( $blog_prefix ) . '%';
		$tables      = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $prefix_like ) );

		if ( empty( $tables ) ) {
			return;
		}

		$tables = array_map(
			static function ( $table ) {
				return '`' . str_replace( '`', '``', $table ) . '`';
			},
			$tables
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table identifiers are escaped and cannot be passed as placeholders.
		$wpdb->query( 'DROP TABLE IF EXISTS ' . implode( ', ', $tables ) );
	}

	/**
	 * Gets details about a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * <site>
	 * : Site ID or URL of the site to get. For subdirectory sites, use the full URL (e.g., http://example.com/subdir/).
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole site, returns the value of a single field.
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
	 * These fields will be displayed by default for the site:
	 *
	 * * blog_id
	 * * url
	 * * last_updated
	 * * registered
	 *
	 * These fields are optionally available:
	 *
	 * * site_id
	 * * domain
	 * * path
	 * * public
	 * * archived
	 * * mature
	 * * spam
	 * * deleted
	 * * lang_id
	 *
	 * ## EXAMPLES
	 *
	 *     # Get site by ID
	 *     $ wp site get 1
	 *     +---------+-------------------------+---------------------+---------------------+
	 *     | blog_id | url                     | last_updated        | registered          |
	 *     +---------+-------------------------+---------------------+---------------------+
	 *     | 1       | http://example.com/     | 2025-01-01 12:00:00 | 2025-01-01 12:00:00 |
	 *     +---------+-------------------------+---------------------+---------------------+
	 *
	 *     # Get site URL by site ID
	 *     $ wp site get 1 --field=url
	 *     http://example.com/
	 *
	 *     # Get site ID by URL
	 *     $ wp site get http://example.com/subdir/ --field=blog_id
	 *     2
	 *
	 *     # Delete a site by URL
	 *     $ wp site delete $(wp site get http://example.com/subdir/ --field=blog_id) --yes
	 *     Success: The site at 'http://example.com/subdir/' was deleted.
	 */
	public function get( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		$site_arg = $args[0];
		$site     = null;

		// Check if the argument is a URL or a domain (non-numeric)
		if ( ! is_numeric( $site_arg ) ) {
			// Normalize URLs without a scheme for proper parsing.
			$url_to_parse = $site_arg;
			if ( false === strpos( $url_to_parse, '://' ) ) {
				$url_to_parse = 'http://' . $url_to_parse;
			}

			// Parse the URL to get domain and path
			$url_parts = wp_parse_url( $url_to_parse );

			if ( ! isset( $url_parts['host'] ) ) {
				WP_CLI::error( "Invalid URL: {$site_arg}" );
			}

			$domain = $url_parts['host'];
			$path   = isset( $url_parts['path'] ) ? $url_parts['path'] : '/';

			// Ensure path ends with /
			if ( '/' !== substr( $path, -1 ) ) {
				$path .= '/';
			}

			// Use WordPress's cached function to get the blog ID
			$blog_id = get_blog_id_from_url( $domain, $path );

			if ( ! $blog_id ) {
				WP_CLI::error( "Could not find site with URL: {$site_arg}" );
			}

			$site = $this->fetcher->get_check( $blog_id );
		} else {
			// Treat as site ID
			$site = $this->fetcher->get_check( $site_arg );
		}

		// Get the site details and add URL
		$site_data        = get_object_vars( $site );
		$site_data['url'] = trailingslashit( get_home_url( $site->blog_id ) );

		// Cast numeric fields to int for consistent output
		$numeric_fields = [ 'blog_id', 'site_id', 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id' ];
		foreach ( $numeric_fields as $field ) {
			if ( isset( $site_data[ $field ] ) && is_scalar( $site_data[ $field ] ) ) {
				$site_data[ $field ] = (int) $site_data[ $field ];
			}
		}

		// Set default fields if not specified
		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = [ 'blog_id', 'url', 'last_updated', 'registered' ];
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $site_data );
	}

	/**
	 * Creates a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [--slug=<slug>]
	 * : Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.
	 * Required if --site-url is not provided.
	 *
	 * [--site-url=<url>]
	 * : Full URL for the new site. Use this to specify a custom domain instead of the auto-generated one.
	 * For subdomain installs, this allows you to use a different base domain (e.g., 'http://site.example.com' instead of 'http://site.main.example.com').
	 * For subdirectory installs, this allows you to use a different path.
	 * If provided, --slug is optional and will be derived from the URL. If both --slug and --site-url are provided, --slug will be used as the base for internal operations (like user creation), while the domain/path from --site-url will be used for the actual site URL.
	 *
	 * [--title=<title>]
	 * : Title of the new site. Default: prettified slug.
	 *
	 * [--email=<email>]
	 * : Email for admin user. User will be created if none exists. Assignment to super admin if not included.
	 *
	 * [--network_id=<network-id>]
	 * : Network to associate new site with. Defaults to current network (typically 1).
	 *
	 * [--private]
	 * : If set, the new site will be non-public (not indexed)
	 *
	 * [--porcelain]
	 * : If set, only the site id will be output on success.
	 *
	 * ## EXAMPLES
	 *
	 *     # Create a site with auto-generated domain
	 *     $ wp site create --slug=example
	 *     Success: Site 3 created: http://www.example.com/example/
	 *
	 *     # Create a site with a custom domain (subdomain multisite)
	 *     $ wp site create --site-url=http://site.example.com
	 *     Success: Site 4 created: http://site.example.com/
	 *
	 *     # Create a site with a custom subdirectory (subdirectory multisite)
	 *     $ wp site create --site-url=http://example.com/custom/path/
	 *     Success: Site 5 created: http://example.com/custom/path/
	 */
	public function create( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		global $wpdb, $current_site;

		// Check if either slug or site-url is provided
		$has_slug     = isset( $assoc_args['slug'] );
		$has_site_url = isset( $assoc_args['site-url'] );

		if ( ! $has_slug && ! $has_site_url ) {
			WP_CLI::error( 'Either --slug or --site-url must be provided.' );
		}

		// If site URL is provided, parse it to get domain and path
		$custom_domain = null;
		$custom_path   = null;
		$base          = null;

		if ( $has_site_url ) {
			$parsed_url = wp_parse_url( $assoc_args['site-url'] );
			if ( ! is_array( $parsed_url ) || ! isset( $parsed_url['host'] ) || ! is_string( $parsed_url['host'] ) ) {
				WP_CLI::error( 'Invalid URL format. Please provide a valid URL (e.g., http://site.example.com).' );
			}

			// Validate the scheme if present
			if ( isset( $parsed_url['scheme'] ) && ! in_array( $parsed_url['scheme'], [ 'http', 'https' ], true ) ) {
				WP_CLI::error( 'Invalid URL scheme. Only http and https schemes are supported.' );
			}

			// Reject unsupported URL components (user, pass, port, query, fragment)
			$unsupported = array_intersect_key( $parsed_url, array_flip( [ 'user', 'pass', 'port', 'query', 'fragment' ] ) );
			if ( ! empty( $unsupported ) ) {
				WP_CLI::error( 'Invalid URL format. User credentials, ports, query parameters, and fragments are not supported in --site-url.' );
			}

			// Sanitize domain and path
			$raw_path      = isset( $parsed_url['path'] ) && is_string( $parsed_url['path'] ) ? $parsed_url['path'] : '/';
			$custom_domain = sanitize_text_field( $parsed_url['host'] );
			$custom_path   = sanitize_text_field( '/' . ltrim( $raw_path, '/' ) );

			$domain_parts = explode( '.', $custom_domain );
			$valid_domain = true;
			foreach ( $domain_parts as $part ) {
				if ( '' === $part || ! preg_match( '/^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$/', $part ) ) {
					$valid_domain = false;
					break;
				}
			}

			if ( ! $valid_domain ) {
				WP_CLI::error( 'Invalid domain format in --site-url.' );
			}

			if ( ! preg_match( '|^[a-zA-Z0-9/-]+$|', $custom_path ) || false !== strpos( $raw_path, '//' ) ) {
				WP_CLI::error( 'Invalid path format in --site-url.' );
			}

			// Ensure path ends with /
			if ( '/' !== substr( $custom_path, -1 ) ) {
				$custom_path .= '/';
			}

			// Derive base/slug from the URL if not explicitly provided
			if ( ! $has_slug ) {
				if ( is_subdomain_install() ) {
					// For subdomain installs, use the first part of the domain as the base
					$domain_parts = explode( '.', $custom_domain );
					$base         = $domain_parts[0];

					// Validate that the derived base is suitable for use as a slug
					if ( empty( $base ) || is_numeric( $base ) ) {
						WP_CLI::error( 'Could not derive a valid slug from the domain (numeric-only or empty slugs are not allowed). Please provide --slug explicitly.' );
					}

					// Sanitize and lowercase the derived base
					$base = strtolower( $base );
				} else {
					// For subdirectory installs, derive slug from the last part of the path.
					$path_parts = array_filter(
						explode( '/', trim( $custom_path, '/' ) ),
						function ( $part ) {
							return '' !== $part;
						}
					);
					$base       = (string) array_pop( $path_parts );

					// If base is empty (root path), require explicit slug.
					if ( empty( $base ) ) {
						WP_CLI::error( 'Could not derive a valid slug from the URL path. Please provide --slug explicitly.' );
					}

					// Sanitize and lowercase the derived base.
					$base = strtolower( $base );
				}
			} else {
				$base = $assoc_args['slug'];
			}
		} else {
			$base = $assoc_args['slug'];
		}

		/**
		 * @var string $title
		 */
		$title = Utils\get_flag_value( $assoc_args, 'title', ucfirst( $base ) );

		$email = empty( $assoc_args['email'] ) ? '' : $assoc_args['email'];

		// Network
		if ( ! empty( $assoc_args['network_id'] ) ) {
			$network = $this->get_network( $assoc_args['network_id'] );
			if ( false === $network ) {
				WP_CLI::error( "Network with id {$assoc_args['network_id']} does not exist." );
			}
		} else {
			$network = $current_site;
		}

		$public = ! Utils\get_flag_value( $assoc_args, 'private' );

		// Sanitize
		if ( ! preg_match( '|^([a-zA-Z0-9-])+$|D', $base ) ) {
			WP_CLI::error( 'Slug may only contain letters, numbers, and dashes.' );
		}
		$base = strtolower( $base );

		// If not a subdomain install, make sure the domain isn't a reserved word
		if ( ! is_subdomain_install() ) {
			$subdirectory_reserved_names = $this->get_subdirectory_reserved_names();
			if ( in_array( $base, $subdirectory_reserved_names, true ) ) {
				WP_CLI::error( 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		// Check for valid email, if not, use the first super admin found
		// Probably a more efficient way to do this so we dont query for the
		// User twice if super admin
		$email = sanitize_email( $email );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$super_admins = get_super_admins();
			$email        = '';
			if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
				// Just get the first one
				$super_login = reset( $super_admins );
				$super_user  = get_user_by( 'login', $super_login );
				if ( $super_user ) {
					$email = $super_user->user_email;
				}
			}
		}

		if ( null !== $custom_domain ) {
			// A custom site URL was provided.
			$newdomain = $custom_domain;
			$path      = $custom_path;

			// For subdirectory installs, warn if the domain is different from the network's domain.
			if ( ! is_subdomain_install() ) {
				$network_domain           = preg_replace( '|^www\.|', '', $current_site->domain );
				$custom_domain_normalized = preg_replace( '|^www\.|', '', $custom_domain );
				if ( $custom_domain_normalized !== $network_domain ) {
					WP_CLI::warning( 'Using a different domain for a subdirectory multisite install may require additional configuration (such as domain mapping) to work properly.' );
				}
			}
		} elseif ( is_subdomain_install() ) {
			// No custom site URL, use the slug to generate the domain/path for subdomain install.
			$newdomain = $base . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
			$path      = $current_site->path;
		} else {
			// No custom site URL, use the slug to generate the domain/path for subdirectory install.
			$newdomain = $current_site->domain;
			$path      = $current_site->path . $base . '/';
		}

		$user_id = email_exists( $email );
		if ( ! $user_id ) { // Create a new user with a random password
			$password = wp_generate_password( 24, false );
			$user_id  = wpmu_create_user( $base, $password, $email );
			if ( false === $user_id ) {
				WP_CLI::error( "Can't create user." );
			} else {
				User_Command::wp_new_user_notification( $user_id, $password );
			}
		}

		$wpdb->hide_errors();
		$title = wp_slash( $title );
		$id    = wpmu_create_blog( $newdomain, $path, $title, $user_id, [ 'public' => $public ], $network->id );
		$wpdb->show_errors();
		if ( ! is_wp_error( $id ) ) {
			if ( ! is_super_admin( $user_id ) && ! get_user_option( 'primary_blog', $user_id ) ) {
				update_user_option( $user_id, 'primary_blog', $id, true );
			}
		} else {
			WP_CLI::error( $id->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( (string) $id );
		} else {
			$site_url = trailingslashit( get_site_url( $id ) );
			WP_CLI::success( "Site {$id} created: {$site_url}" );
		}
	}

	/**
	 * Generate some sites.
	 *
	 * Creates a specified number of new sites.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many sites to generates?
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--slug=<slug>]
	 * : Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--email=<email>]
	 * : Email for admin user. User will be created if none exists. Assignment to super admin if not included.
	 *
	 * [--network_id=<network-id>]
	 * : Network to associate new site with. Defaults to current network (typically 1).
	 *
	 * [--private]
	 * : If set, the new site will be non-public (not indexed)
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *  - progress
	 *  - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *    # Generate 10 sites.
	 *    $ wp site generate --count=10
	 *    Generating sites  100% [================================================] 0:01 / 0:04
	 */
	public function generate( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		global $wpdb, $current_site;

		$defaults = [
			'count'      => 100,
			'email'      => '',
			'network_id' => 1,
			'slug'       => 'site',
		];

		$assoc_args = array_merge( $defaults, $assoc_args );

		// Base.
		$base = $assoc_args['slug'];
		if ( ! preg_match( '|^([a-zA-Z0-9-])+$|D', $base ) ) {
			WP_CLI::error( 'Slug may only contain letters, numbers, and dashes.' );
		}
		$base = strtolower( $base );

		$is_subdomain_install = is_subdomain_install();
		// If not a subdomain install, make sure the domain isn't a reserved word
		if ( ! $is_subdomain_install ) {
			$subdirectory_reserved_names = $this->get_subdirectory_reserved_names();
			if ( in_array( $base, $subdirectory_reserved_names, true ) ) {
				WP_CLI::error( 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		// Network.
		if ( ! empty( $assoc_args['network_id'] ) ) {
			$network = $this->get_network( $assoc_args['network_id'] );
			if ( false === $network ) {
				WP_CLI::error( "Network with id {$assoc_args['network_id']} does not exist." );
			}
		} else {
			$network = $current_site;
		}

		// Public.
		$public = ! Utils\get_flag_value( $assoc_args, 'private' );

		// Limit.
		$limit = $assoc_args['count'];

		// Email.
		$email = sanitize_email( $assoc_args['email'] );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$super_admins = get_super_admins();
			$email        = '';
			if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
				$super_login = reset( $super_admins );
				$super_user  = get_user_by( 'login', $super_login );
				if ( $super_user ) {
					$email = $super_user->user_email;
				}
			}
		}

		$user_id = email_exists( $email );
		if ( ! $user_id ) {
			$password = wp_generate_password( 24, false );
			$user_id  = wpmu_create_user( $base . '-admin', $password, $email );

			if ( false === $user_id ) {
				WP_CLI::error( "Can't create user." );
			} else {
				User_Command::wp_new_user_notification( $user_id, $password );
			}
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = Utils\make_progress_bar( 'Generating sites', $limit );
		}

		for ( $index = 1; $index <= $limit; $index++ ) {
			$current_base = $base . $index;
			$title        = ucfirst( $base ) . ' ' . $index;

			if ( $is_subdomain_install ) {
				$new_domain = $current_base . '.' . preg_replace( '|^www\.|', '', $network->domain );
				$path       = $network->path;
			} else {
				$new_domain = $network->domain;
				$path       = $network->path . $current_base . '/';
			}

			$wpdb->hide_errors();
			$title = wp_slash( $title );
			$id    = wpmu_create_blog( $new_domain, $path, $title, $user_id, [ 'public' => $public ], $network->id );
			$wpdb->show_errors();
			if ( ! is_wp_error( $id ) ) {
				if ( ! is_super_admin( $user_id ) && ! get_user_option( 'primary_blog', $user_id ) ) {
					update_user_option( $user_id, 'primary_blog', $id, true );
				}
			} else {
				WP_CLI::error( $id->get_error_message() );
			}

			if ( 'progress' === $format ) {
				$notify->tick();
			} else {
				echo $id;
				if ( $index < $limit - 1 ) {
					echo ' ';
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		}
	}

	/**
	 * Retrieves a list of reserved site on a sub-directory Multisite installation.
	 *
	 * Works on older WordPress versions where get_subdirectory_reserved_names() does not exist.
	 *
	 * @return string[] Array of reserved names.
	 */
	private function get_subdirectory_reserved_names() {
		if ( function_exists( 'get_subdirectory_reserved_names' ) ) {
			return get_subdirectory_reserved_names();
		}

		$names = array(
			'page',
			'comments',
			'blog',
			'files',
			'feed',
			'wp-admin',
			'wp-content',
			'wp-includes',
			'wp-json',
			'embed',
		);

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling WordPress native hook.
		return apply_filters( 'subdirectory_reserved_names', $names );
	}

	/**
	 * Gets network data for a given id.
	 *
	 * @param int     $network_id
	 * @return bool|array False if no network found with given id, array otherwise
	 */
	private function get_network( $network_id ) {
		global $wpdb;

		// Load network data
		$networks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->site WHERE id = %d",
				$network_id
			)
		);

		if ( ! empty( $networks ) ) {
			// Only care about domain and path which are set here
			return $networks[0];
		}

		return false;
	}

	/**
	 * Lists all sites in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [--network=<id>]
	 * : The network to which the sites belong.
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see "Available Fields" section), or pass any
	 * other argument accepted by WP_Site_Query, such as 'search', 'site__not_in',
	 * 'number', 'offset', 'orderby' or 'order'. However, 'url' isn't an available
	 * filter, as it comes from 'home' in wp_options.
	 * Note: '--path' conflicts with the global parameter of the same name; use
	 * '--site-path' to filter by path instead.
	 *
	 * [--site__in=<value>]
	 * : Only list the sites with these blog_id values (comma-separated).
	 *
	 * [--site_user=<value>]
	 * : Only list the sites with this user.
	 *
	 * [--site-path=<path>]
	 * : Filter by path. Avoids conflict with the global `--path` parameter.
	 *
	 * [--blog_id=<blog_id>]
	 * : Filter by site ID.
	 *
	 * [--site_id=<site_id>]
	 * : Filter by the ID of the network the site belongs to. `--network` is an
	 * alias for this, and takes precedence when both are given.
	 *
	 * [--domain=<domain>]
	 * : Filter by domain.
	 *
	 * [--registered=<date>]
	 * : Filter by the date the site was registered. Accepts a timestamp or a
	 * date; a value carrying no time of day matches that whole day.
	 *
	 * [--last_updated=<date>]
	 * : Filter by the date the site was last updated. Accepts a timestamp or a
	 * date; a value carrying no time of day matches that whole day.
	 *
	 * [--public=<public>]
	 * : Filter by whether the site is public. Accepts 1 or 0.
	 *
	 * [--archived=<archived>]
	 * : Filter by whether the site is archived. Accepts 1 or 0.
	 *
	 * [--mature=<mature>]
	 * : Filter by whether the site is flagged as mature. Accepts 1 or 0.
	 *
	 * [--spam=<spam>]
	 * : Filter by whether the site is flagged as spam. Accepts 1 or 0.
	 *
	 * [--deleted=<deleted>]
	 * : Filter by whether the site is flagged as deleted. Accepts 1 or 0.
	 *
	 * [--lang_id=<lang_id>]
	 * : Filter by language ID.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each site.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to show.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - count
	 *   - ids
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each site:
	 *
	 * * blog_id
	 * * url
	 * * last_updated
	 * * registered
	 *
	 * These fields are optionally available:
	 *
	 * * site_id
	 * * domain
	 * * path
	 * * public
	 * * archived
	 * * mature
	 * * spam
	 * * deleted
	 * * lang_id
	 *
	 * ## EXAMPLES
	 *
	 *     # Output a simple list of site URLs
	 *     $ wp site list --field=url
	 *     http://www.example.com/
	 *     http://www.example.com/subdir/
	 *
	 *     # Output site URLs, most recently registered first
	 *     $ wp site list --orderby=registered --order=desc --field=url
	 *     http://www.example.com/subdir/
	 *     http://www.example.com/
	 *
	 *     # Search for sites by domain or path
	 *     $ wp site list --search=subdir --field=url
	 *     http://www.example.com/subdir/
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			WP_CLI::error( 'This is not a multisite installation.' );
		}

		if ( isset( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = preg_split( '/,[ \t]*/', $assoc_args['fields'] );
		}

		$defaults   = [
			'format' => 'table',
			'fields' => [ 'blog_id', 'url', 'last_updated', 'registered' ],
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		// Anything the command does not consume itself is handed to WP_Site_Query,
		// which is what makes its own arguments - search, site__not_in, date_query,
		// lang__in and the rest - usable here.
		//
		// 'count' is withheld deliberately: it makes get_sites() return an integer
		// rather than a list, and '--format=count' is how this command spells it.
		$query_args = array_diff_key(
			$assoc_args,
			array_flip(
				[ 'format', 'fields', 'field', 'count', 'blog_id', 'site_id', 'site_user', 'site-path', 'network', 'registered', 'last_updated' ]
			)
		);

		// Arguments this command spells differently to WP_Site_Query.
		if ( isset( $assoc_args['blog_id'] ) ) {
			$query_args['site__in'] = [ $assoc_args['blog_id'] ];
		}

		if ( isset( $assoc_args['site__in'] ) ) {
			$query_args['site__in'] = array_map( 'trim', explode( ',', $assoc_args['site__in'] ) );
		}

		if ( isset( $assoc_args['site_id'] ) ) {
			$query_args['network_id'] = $assoc_args['site_id'];
		}

		// '--network' has always taken precedence over '--site_id'.
		if ( isset( $assoc_args['network'] ) ) {
			$query_args['network_id'] = $assoc_args['network'];
		}

		if ( isset( $assoc_args['site-path'] ) ) {
			$query_args['path'] = $assoc_args['site-path'];
		}

		// WP_Site_Query only reaches the date columns through a date query. Bounding
		// the range by the given value on both ends matches it as precisely as it was
		// written: a full timestamp matches that second, a date matches that day.
		// Interpreting the value is WP_Date_Query's job, so it is passed on as given.
		$date_query = [];

		foreach ( [ 'registered', 'last_updated' ] as $column ) {
			if ( isset( $assoc_args[ $column ] ) ) {
				$date_query[] = [
					'column'    => $column,
					'after'     => $assoc_args[ $column ],
					'before'    => $assoc_args[ $column ],
					'inclusive' => true,
				];
			}
		}

		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		if ( isset( $assoc_args['site_user'] ) ) {
			$user     = ( new UserFetcher() )->get_check( $assoc_args['site_user'] );
			$user_ids = [];

			if ( $user ) {
				/**
				 * @phpstan-var UserSite[] $blogs
				 */
				$blogs = get_blogs_of_user( $user->ID );

				foreach ( $blogs as $blog ) {
					$user_ids[] = $blog->userblog_id;
				}
			}

			if ( isset( $query_args['site__in'] ) ) {
				$user_ids = array_intersect( array_map( 'intval', $query_args['site__in'] ), $user_ids );
			}

			if ( empty( $user_ids ) ) {
				$formatter = new Formatter( $assoc_args, [], 'site' );
				$formatter->display_items( [] );
				return;
			}

			$query_args['site__in'] = array_values( $user_ids );
		}

		// Listing by explicit IDs has always come back in the order they were given.
		if ( isset( $query_args['site__in'] ) && ! isset( $assoc_args['orderby'] ) ) {
			$query_args['orderby'] = 'site__in';
		}

		$iterator = Utils\iterator_map(
			self::get_sites_iterator( $query_args ),
			function ( $site ) {
				$site_data        = $site->to_array();
				$site_data['url'] = trailingslashit( get_home_url( $site->blog_id ) );

				return (object) $site_data;
			}
		);

		if ( ! empty( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$sites     = iterator_to_array( $iterator );
			$ids       = wp_list_pluck( $sites, 'blog_id' );
			$formatter = new Formatter( $assoc_args, null, 'site' );
			$formatter->display_items( $ids );
		} else {
			$formatter = new Formatter( $assoc_args, null, 'site' );
			$formatter->display_items( $iterator );
		}
	}

	/**
	 * Yields sites a page at a time.
	 *
	 * The previous implementation read $wpdb->blogs through a chunked iterator, so
	 * listing a large network never held every row in memory at once. Page through
	 * WP_Site_Query the same way, unless an explicit --number was given, in which
	 * case that is the caller's own limit and is passed straight through.
	 *
	 * @param array<string, mixed> $query_args Arguments for WP_Site_Query.
	 * @return \Generator<int, \WP_Site>
	 */
	private static function get_sites_iterator( $query_args ) {
		if ( isset( $query_args['number'] ) ) {
			// The arguments are whatever the user passed, so they cannot be narrowed
			// to the shape get_sites() documents. WP_Site_Query validates them itself.
			// @phpstan-ignore argument.type
			foreach ( get_sites( $query_args ) as $site ) {
				yield $site;
			}

			return;
		}

		$chunk_size = 500;
		$offset     = isset( $query_args['offset'] ) && is_numeric( $query_args['offset'] )
			? (int) $query_args['offset']
			: 0;

		do {
			$page_args = array_merge(
				$query_args,
				[
					'number' => $chunk_size,
					'offset' => $offset,
				]
			);

			// @phpstan-ignore argument.type
			$sites = get_sites( $page_args );

			foreach ( $sites as $site ) {
				yield $site;
			}

			$fetched = count( $sites );
			$offset += $chunk_size;
		} while ( $fetched === $chunk_size );
	}

	/**
	 * Archives one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to archive. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to archive. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site archive 123
	 *     Success: Site 123 archived.
	 *
	 *     $ wp site archive --slug=demo
	 *     Success: Site 123 archived.
	 */
	public function archive( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'archived', 1 );
	}

	/**
	 * Unarchives one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to unarchive. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to unarchive. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site unarchive 123
	 *     Success: Site 123 unarchived.
	 *
	 *     $ wp site unarchive --slug=demo
	 *     Success: Site 123 unarchived.
	 */
	public function unarchive( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'archived', 0 );
	}

	/**
	 * Activates one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to activate. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be activated. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site activate 123
	 *     Success: Site 123 activated.
	 *
	 *      $ wp site activate --slug=demo
	 *      Success: Site 123 marked as activated.
	 */
	public function activate( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'deleted', 0 );
	}

	/**
	 * Deactivates one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to deactivate. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be deactivated. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site deactivate 123
	 *     Success: Site 123 deactivated.
	 *
	 *     $ wp site deactivate --slug=demo
	 *     Success: Site 123 deactivated.
	 */
	public function deactivate( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'deleted', 1 );
	}

	/**
	 * Marks one or more sites as spam.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to be marked as spam. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be marked as spam. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site spam 123
	 *     Success: Site 123 marked as spam.
	 */
	public function spam( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'spam', 1 );
	}

	/**
	 * Removes one or more sites from spam.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to remove from spam. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be removed from spam. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site unspam 123
	 *     Success: Site 123 removed from spam.
	 *
	 * @subcommand unspam
	 */
	public function unspam( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'spam', 0 );
	}

	/**
	 * Sets one or more sites as mature.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as mature. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as mature. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site mature 123
	 *     Success: Site 123 marked as mature.
	 *
	 *     $ wp site mature --slug=demo
	 *     Success: Site 123 marked as mature.
	 */
	public function mature( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'mature', 1 );
	}

	/**
	 * Sets one or more sites as immature.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as unmature. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as unmature. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site unmature 123
	 *     Success: Site 123 marked as unmature.
	 *
	 *     $ wp site unmature --slug=demo
	 *     Success: Site 123 marked as unmature.
	 */
	public function unmature( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'mature', 0 );
	}

	/**
	 * Sets one or more sites as public.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as public. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as public. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site public 123
	 *     Success: Site 123 marked as public.
	 *
	 *      $ wp site public --slug=demo
	 *      Success: Site 123 marked as public.
	 *
	 * @subcommand public
	 */
	public function set_public( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'public', 1 );
	}

	/**
	 * Sets one or more sites as private.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as private. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as private. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp site private 123
	 *     Success: Site 123 marked as private.
	 *
	 *     $ wp site private --slug=demo
	 *     Success: Site 123 marked as private.
	 *
	 * @subcommand private
	 */
	public function set_private( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'public', 0 );
	}

	private function update_site_status( $ids, $pref, $value ) {
		$value = (int) $value;

		$action = 'updated';

		switch ( $pref ) {
			case 'archived':
				$action = $value ? 'archived' : 'unarchived';
				break;
			case 'deleted':
				$action = $value ? 'deactivated' : 'activated';
				break;
			case 'mature':
				$action = $value ? 'marked as mature' : 'marked as unmature';
				break;
			case 'public':
				$action = $value ? 'marked as public' : 'marked as private';
				break;
			case 'spam':
				$action = $value ? 'marked as spam' : 'removed from spam';
				break;
		}

		foreach ( $ids as $site_id ) {
			$site = $this->fetcher->get_check( $site_id );

			if ( is_main_site( $site->blog_id ) ) {
				WP_CLI::warning( 'You are not allowed to change the main site.' );
				continue;
			}

			$old_value = (int) get_blog_status( $site->blog_id, $pref );

			if ( $value === $old_value ) {
				WP_CLI::warning( "Site {$site->blog_id} already {$action}." );
				continue;
			}

			update_blog_status( $site->blog_id, $pref, (string) $value );
			WP_CLI::success( "Site {$site->blog_id} {$action}." );
		}
	}

	/**
	 * Get an array of site IDs from the passed-in arguments or slug parameter.
	 *
	 * @param array $args Passed-in arguments.
	 * @param array $assoc_args Passed-in parameters.
	 *
	 * @return array Site IDs.
	 * @throws ExitException
	 */
	private function get_sites_ids( $args, $assoc_args ) {
		/**
		 * @var string|false $slug
		 */
		$slug = Utils\get_flag_value( $assoc_args, 'slug', false );

		if ( $slug ) {
			$blog_id = get_id_from_blogname( trim( $slug, '/' ) );
			if ( null === $blog_id ) {
				WP_CLI::error( sprintf( 'Could not find site with slug \'%s\'.', $slug ) );
			}
			return [ $blog_id ];
		}

		return $args;
	}

	/**
	 * Check that the site IDs or slug are provided.
	 *
	 * @param  array  $args  Passed-in arguments.
	 * @param  array  $assoc_args  Passed-in parameters.
	 *
	 * @return bool
	 */
	private function check_site_ids_and_slug( $args, $assoc_args ) {
		if ( ( empty( $args ) && empty( $assoc_args['slug'] ) )
			|| ( ! empty( $args ) && ! empty( $assoc_args['slug'] ) ) ) {
			WP_CLI::error( 'Please specify one or more IDs of sites, or pass the slug for a single site using --slug.' );
		}

		return true;
	}
}
