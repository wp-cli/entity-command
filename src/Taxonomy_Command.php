<?php
/**
 * Retrieves information about registered taxonomies.
 *
 * See references for [built-in taxonomies](https://developer.wordpress.org/themes/basics/categories-tags-custom-taxonomies/) and [custom taxonomies](https://developer.wordpress.org/plugins/taxonomies/working-with-custom-taxonomies/).
 *
 * ## EXAMPLES
 *
 *     # List all taxonomies with 'post' object type.
 *     $ wp taxonomy list --object_type=post --fields=name,public
 *     +-------------+--------+
 *     | name        | public |
 *     +-------------+--------+
 *     | category    | 1      |
 *     | post_tag    | 1      |
 *     | post_format | 1      |
 *     +-------------+--------+
 *
 *     # Get capabilities of 'post_tag' taxonomy.
 *     $ wp taxonomy get post_tag --field=cap
 *     {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}
 *
 * @package wp-cli
 */
class Taxonomy_Command extends WP_CLI_Command {

	private $fields = array(
		'name',
		'label',
		'description',
		'object_type',
		'show_tagcloud',
		'hierarchical',
		'public',
	);

	public function __construct() {

		if ( \WP_CLI\Utils\wp_version_compare( 3.7, '<' ) ) {
			// remove description for wp <= 3.7
			$this->fields = array_values( array_diff( $this->fields, array( 'description' ) ) );
		}

		parent::__construct();
	}

	/**
	 * Lists registered taxonomies.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see get_taxonomies() first parameter for a list of available fields).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each taxonomy.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific taxonomy fields.
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
	 * These fields will be displayed by default for each term:
	 *
	 * * name
	 * * label
	 * * description
	 * * public
	 * * hierarchical
	 *
	 * There are no optionally available fields.
	 *
	 * ## EXAMPLES
	 *
	 *     # List all taxonomies.
	 *     $ wp taxonomy list --format=csv
	 *     name,label,description,object_type,show_tagcloud,hierarchical,public
	 *     category,Categories,,post,1,1,1
	 *     post_tag,Tags,,post,1,,1
	 *     nav_menu,"Navigation Menus",,nav_menu_item,,,
	 *     link_category,"Link Categories",,link,1,,
	 *     post_format,Format,,post,,,1
	 *
	 *     # List all taxonomies with 'post' object type.
	 *     $ wp taxonomy list --object_type=post --fields=name,public
	 *     +-------------+--------+
	 *     | name        | public |
	 *     +-------------+--------+
	 *     | category    | 1      |
	 *     | post_tag    | 1      |
	 *     | post_format | 1      |
	 *     +-------------+--------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		if ( isset( $assoc_args['object_type'] ) ) {
			$assoc_args['object_type'] = array( $assoc_args['object_type'] );
		}

		$taxonomies = get_taxonomies( $assoc_args, 'objects' );

		$taxonomies = array_map( function( $taxonomy ) {
			$taxonomy->object_type = implode( ', ', $taxonomy->object_type );
			return $taxonomy;
		}, $taxonomies );

		$formatter->display_items( $taxonomies );
	}

	/**
	 * Gets details about a registered taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : Taxonomy slug.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole taxonomy, returns the value of a single field.
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
	 * ## EXAMPLES
	 *
	 *     # Get details of `category` taxonomy.
	 *     $ wp taxonomy get category --fields=name,label,object_type
	 *     +-------------+------------+
	 *     | Field       | Value      |
	 *     +-------------+------------+
	 *     | name        | category   |
	 *     | label       | Categories |
	 *     | object_type | ["post"]   |
	 *     +-------------+------------+
	 *
	 *     # Get capabilities of 'post_tag' taxonomy.
	 *     $ wp taxonomy get post_tag --field=cap
	 *     {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}
	 */
	public function get( $args, $assoc_args ) {
		$taxonomy = get_taxonomy( $args[0] );

		if ( ! $taxonomy ) {
			WP_CLI::error( "Taxonomy {$args[0]} doesn't exist." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$default_fields = array_merge( $this->fields, array(
				'labels',
				'cap'
			) );

			$assoc_args['fields'] = $default_fields;
		}

		$data = array(
			'name'          => $taxonomy->name,
			'label'         => $taxonomy->label,
			'description'   => $taxonomy->description,
			'object_type'   => $taxonomy->object_type,
			'show_tagcloud' => $taxonomy->show_tagcloud,
			'hierarchical'  => $taxonomy->hierarchical,
			'public'        => $taxonomy->public,
			'labels'        => $taxonomy->labels,
			'cap'           => $taxonomy->cap,
		);

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $data );
	}



	/**
	 * Migrate a term of a taxonomy to another taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * <term>
	 * : Slug of the term to migrate.
	 *
	 * <orig-taxonomy>
	 * : Taxonomy slug of the term to migrate.
	 *
	 * <dest-taxonomy>
	 * : Taxonomy slug to migrate.
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Migrate a category's term (video) to tag taxonomy.
	 *     $ wp taxonomy migrate video category post_tag
	 *     Term video has migrated from category taxonomy to post_tag taxonomy.
	 */
	public function migrate( $args, $assoc_args ) {
		// Code based from https://wordpress.org/plugins/taxonomy-converter/ plugin
		global $wpdb;
		$clean_term_cache = array();
		$exists = term_exists( $args[0], $args[1] );

		if ( ! empty( $exists ) ) {
			WP_CLI::error( "Taxonomy {$args[0]} doesn't exist." );
		}

		$original_taxonomy = get_taxonomy( $args[0] );
		$term = get_term( $args[0], $args[1] );

		$id = wp_insert_term($term->name, $args[2], array( 'slug' => $term->slug ) );

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( "An error has occured: " . $id->get_error_message() );
		}

		$id = $id['term_taxonomy_id'];
		$posts = get_objects_in_term( $term->term_id, $args[1] );
		$term_order = 0;

		foreach ( $posts as $post ) {
			$type = get_post_type( $post );
			if ( in_array( $type, $original_taxonomy->object_type ) ) {
				$values[] = $wpdb->prepare( "(%d, %d, %d)", $post, $id, $term_order );
			}

			clean_post_cache( $post );
		}

		if ( $values ) {
			$wpdb->query( "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES " . join(',', $values) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)" );
			$wpdb->update($wpdb->term_taxonomy, array( 'count' => $term->count ), array( 'term_id' => $term->term_id, 'taxonomy' => $args[2] ) );

			WP_CLI::success( "Term added to posts." );

			$clean_term_cache[] = $term->term_id;
		}

		$del = wp_delete_term( $term_id, $tax );

		// Set all parents to 0 (root-level) if their parent was the converted tag
		$wpdb->update( $wpdb->term_taxonomy, array( 'parent' => 0 ), array( 'parent' => $term_id, 'taxonomy' => $tax )  );

		if ( ! empty( $clean_term_cache ) ) {
			$clean_term_cache = array_unique( array_values( $clean_term_cache ) );
			clean_term_cache ( $clean_term_cache, $new_tax );
		}
	}

	private function get_formatter( &$assoc_args ) {
		return new \WP_CLI\Formatter( $assoc_args, $this->fields, 'taxonomy' );
	}
}
