<?php

use WP_CLI\CommandWithTerms;

/**
 * Adds, updates, removes, and lists user terms.
 *
 * ## EXAMPLES
 *
 *     # Set user terms
 *     $ wp user term set 123 test category
 *     Success: Set terms.
 */
class User_Term_Command extends CommandWithTerms {
	protected $obj_type = 'user';

	/**
	 * List all terms associated with a user.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : ID for the user.
	 *
	 * <taxonomy>...
	 * : One or more taxonomies to list.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each term.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific row fields.
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
	 *   - ids
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each term:
	 *
	 * * term_id
	 * * name
	 * * slug
	 * * taxonomy
	 *
	 * These fields are optionally available:
	 *
	 * * term_taxonomy_id
	 * * description
	 * * term_group
	 * * parent
	 * * count
	 *
	 * @subcommand list
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function list_( $args, $assoc_args ) {
		parent::list_( $args, $assoc_args );
	}

	/**
	 * Remove a term from a user.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the user.
	 *
	 * <taxonomy>
	 * : The name of the term's taxonomy.
	 *
	 * [<term>...]
	 * : The slug of the term or terms to be removed from the user.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: slug
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 *
	 * [--all]
	 * : Remove all terms from the user.
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function remove( $args, $assoc_args ) {
		parent::remove( $args, $assoc_args );
	}

	/**
	 * Add a term to a user.
	 *
	 * Append the term to the existing set of terms on the user.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the user.
	 *
	 * <taxonomy>
	 * : The name of the taxonomy type to be added.
	 *
	 * <term>...
	 * : The slug of the term or terms to be added.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: slug
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function add( $args, $assoc_args ) {
		parent::add( $args, $assoc_args );
	}

	/**
	 * Set user terms.
	 *
	 * Replaces existing terms on the user.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the user.
	 *
	 * <taxonomy>
	 * : The name of the taxonomy type to be updated.
	 *
	 * <term>...
	 * : The slug of the term or terms to be updated.
	 *
	 * [--by=<field>]
	 * : Explicitly handle the term value as a slug or id.
	 * ---
	 * default: slug
	 * options:
	 *   - slug
	 *   - id
	 * ---
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found -- Override to provide specific documentation.
	public function set( $args, $assoc_args ) {
		parent::set( $args, $assoc_args );
	}
}
