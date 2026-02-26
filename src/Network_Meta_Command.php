<?php

use WP_CLI\CommandWithMeta;

/**
 * Gets, adds, updates, deletes, and lists network custom fields.
 *
 * ## EXAMPLES
 *
 *     # Get a list of super-admins
 *     $ wp network meta get 1 site_admins
 *     array (
 *       0 => 'supervisor',
 *     )
 */
class Network_Meta_Command extends CommandWithMeta {
	protected $meta_type = 'site';

	/**
	 * Override add_metadata() to use add_network_option() if available.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param bool   $unique     Optional, default is false. Whether the
	 *                           specified metadata key should be unique for the
	 *                           object. If true, and the object already has a
	 *                           value for the specified metadata key, no change
	 *                           will be made.
	 *
	 * @return int|false The meta ID on success, false on failure.
	 */
	protected function add_metadata( $object_id, $meta_key, $meta_value, $unique = false ) {
		if ( function_exists( 'add_network_option' ) && $unique ) {
			return add_network_option( $object_id, $meta_key, $meta_value );
		}
		return add_metadata( $this->meta_type, $object_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Override update_metadata() to use update_network_option() if available.
	 *
	 * @param int    $object_id  ID of the object the metadata is for.
	 * @param string $meta_key   Metadata key to use.
	 * @param mixed  $meta_value Metadata value. Must be serializable if
	 *                           non-scalar.
	 * @param mixed  $prev_value Optional. If specified, only update existing
	 *                           metadata entries with the specified value.
	 *                           Otherwise, update all entries.
	 *
	 * @return int|bool Meta ID if the key didn't exist, true on successful
	 *                  update, false on failure.
	 */
	protected function update_metadata( $object_id, $meta_key, $meta_value, $prev_value = '' ) {
		if ( function_exists( 'update_network_option' ) && '' === $prev_value ) {
			return update_network_option( $object_id, $meta_key, $meta_value );
		}
		return update_metadata( $this->meta_type, $object_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Override get_metadata() to use get_network_option() if available.
	 *
	 * @param int    $object_id ID of the object the metadata is for.
	 * @param string $meta_key  Optional. Metadata key. If not specified,
	 *                          retrieve all metadata for the specified object.
	 * @param bool   $single    Optional, default is false. If true, return only
	 *                          the first value of the specified meta_key. This
	 *                          parameter has no effect if meta_key is not
	 *                          specified.
	 *
	 * @return mixed Single metadata value, or array of values.
	 */
	protected function get_metadata( $object_id, $meta_key = '', $single = false ) {
		if ( function_exists( 'get_network_option' ) && '' !== $meta_key && $single ) {
			return get_network_option( $object_id, $meta_key );
		}
		return get_metadata( $this->meta_type, $object_id, $meta_key, $single );
	}

	/**
	 * Override delete_metadata() to use delete_network_option() if available.
	 *
	 * @param int    $object_id  ID of the object metadata is for
	 * @param string $meta_key   Metadata key
	 * @param mixed $meta_value  Optional. Metadata value. Must be serializable
	 *                           if non-scalar. If specified, only delete
	 *                           metadata entries with this value. Otherwise,
	 *                           delete all entries with the specified meta_key.
	 *                           Pass `null, `false`, or an empty string to skip
	 *                           this check. For backward compatibility, it is
	 *                           not possible to pass an empty string to delete
	 *                           those entries with an empty string for a value.
	 *
	 * @return bool True on successful delete, false on failure.
	 */
	protected function delete_metadata( $object_id, $meta_key, $meta_value = '' ) {
		if ( function_exists( 'delete_network_option' ) && '' === $meta_value ) {
			return delete_network_option( $object_id, $meta_key );
		}
		return delete_metadata( $this->meta_type, $object_id, $meta_key, $meta_value, false );
	}
}
