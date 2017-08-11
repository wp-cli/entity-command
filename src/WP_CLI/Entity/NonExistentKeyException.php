<?php

namespace WP_CLI\Entity;

class NonExistentKeyException extends \OutOfBoundsException {
	/* @var \WP_CLI\Entity\RecursiveDataStructureTraverser */
	protected $traverser;

	/**
	 * @param \WP_CLI\Entity\RecursiveDataStructureTraverser $traverser
	 */
	public function set_traverser( $traverser ) {
		$this->traverser = $traverser;
	}

	/**
	 * @return \WP_CLI\Entity\RecursiveDataStructureTraverser
	 */
	public function get_traverser() {
		return $this->traverser;
	}
}
