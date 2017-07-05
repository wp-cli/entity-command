<?php

namespace WP_CLI;

class RecursiveDataStructureTraverser {

	/**
	 * @var mixed The data to traverse set by reference.
	 */
	protected $data;

	/**
	 * @var string The character/sequence used to delineate hierarchy in a single key.
	 */
	protected $delimiter;

	/**
	 * @var null|string The key the data belongs to in the parent's data.
	 */
	protected $key;

	/**
	 * @var null|static The parent instance of the traverser.
	 */
	protected $parent;

	/**
	 * RecursiveDataStructureTraverser constructor.
	 *
	 * @param        $data
	 * @param static $parent
	 */
	public function __construct( &$data, $parent = null ) {
		$this->data =& $data;
		$this->parent = $parent;
	}

	/**
	 * @param $locator
	 *
	 * @throws \Exception
	 *
	 * @return static
	 */
	public function get( $locator ) {
		return $this->traverse_to( $locator )->value();
	}

	/**
	 * Get the current data.
	 *
	 * @return mixed
	 */
	public function value() {
		return $this->data;
	}

	public function set( $locator, $value ) {
		$this->traverse_to( $locator )->set_value( $value );
	}

	public function set_value( $value ) {
		$this->data = $value;
	}

	public function delete( $locator ) {
		$this->traverse_to( $locator )->unset_on_parent();
	}

	/**
	 * Delete the key on the parent's data that references this data.
	 */
	public function unset_on_parent() {
		$this->parent->delete_by_key( $this->key );
	}

	/**
	 * Delete the given key from the data.
	 *
	 * @param $key
	 */
	public function delete_by_key( $key ) {
		if ( is_array( $this->data ) ) {
			unset( $this->data[ $key ] );
		} else {
			unset( $this->data->$key );
		}
	}

	/**
	 * Get an instance of the traverser for the given hierarchical key.
	 *
	 * @param $locator
	 *
	 * @throws \Exception
	 *
	 * @return static
	 */
	public function traverse_to( $locator ) {
		if ( 0 < strlen( $locator ) ) {
			list( $current, $locator ) = $this->parse_locator( $locator );

			if ( ! $this->exists( $current ) ) {
				throw new \Exception( "No data exists for $current \n " . print_r( $this->data, true ) );
			}

			foreach ( $this->data as $key => &$key_data ) {
				if ( $key === $current ) {
					$traverser = new static( $key_data, $this );
					$traverser->set_key( $key );
					$traverser->set_delimiter( $this->delimiter );
					return $traverser->traverse_to( $locator );
				}
			}
		}

		return $this;
	}

	public function set_key( $key ) {
		$this->key = $key;
	}

	public function set_delimiter( $delimiter ) {
		$this->delimiter = $delimiter;
	}

	/**
	 * Check if the given key exists on the current data.
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	public function exists( $key ) {
		return ( is_array( $this->data ) && array_key_exists( $key, $this->data ) ) || ( is_object( $this->data ) && property_exists( $this->data, $key ) );
	}

	protected function parse_locator( $locator ) {
		$parsed = array(
			'current' => $locator,
			'locator' => false,
		);

		if ( 0 < strlen( $this->delimiter ) ) {
			$segments = explode( $this->delimiter, $locator, 2 );
			$parsed['current'] = array_shift( $segments );
			$parsed['locator'] = array_shift( $segments );
		}

		return array_values( $parsed );
	}
}