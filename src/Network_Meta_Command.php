<?php

/**
 * Manage network custom fields.
 *
 * ## EXAMPLES
 *
 *     # Get a list of super-admins
 *     $ wp network meta get 1 site_admins
 *     array (
 *       0 => 'supervisor',
 *     )
 */
class Network_Meta_Command extends \WP_CLI\CommandWithMeta {
	protected $meta_type = 'site';
}
