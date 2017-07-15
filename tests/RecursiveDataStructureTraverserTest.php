<?php

namespace WP_CLI;

class RecursiveDataStructureTraverserTest extends \PHPUnit_Framework_TestCase {

	/** @test */
	function it_can_get_a_top_level_array_value() {
		$array = array(
			'foo' => 'bar',
		);

		$traverser = new RecursiveDataStructureTraverser( $array );

		$this->assertEquals( 'bar', $traverser->get( 'foo' ) );
	}

	/** @test */
	function it_can_get_a_top_level_object_value() {
		$object = (object) array(
			'foo' => 'bar',
		);

		$traverser = new RecursiveDataStructureTraverser( $object );

		$this->assertEquals( 'bar', $traverser->get( 'foo' ) );
	}

	/** @test */
	function it_can_get_a_nested_array_value() {
		$array = array(
			'foo' => array(
				'bar' => array(
					'baz' => 'value'
				),
			),
		);

		$traverser = new RecursiveDataStructureTraverser( $array );

		$this->assertEquals( 'value', $traverser->get( array( 'foo', 'bar', 'baz' ) ) );
	}

	/** @test */
	function it_can_get_a_nested_object_value() {
		$object = (object) array(
			'foo' => (object) array(
				'bar' => 'baz',
			),
		);

		$traverser = new RecursiveDataStructureTraverser( $object );

		$this->assertEquals( 'baz', $traverser->get( array( 'foo', 'bar' ) ) );
	}

	/** @test */
	function it_can_set_a_nested_array_value() {
		$array = array(
			'foo' => array(
				'bar' => 'baz',
			),
		);
		$this->assertEquals( 'baz', $array['foo']['bar'] );

		$traverser = new RecursiveDataStructureTraverser( $array );
		$traverser->update( array( 'foo', 'bar' ), 'new' );

		$this->assertEquals( 'new', $array['foo']['bar'] );
	}

	/** @test */
	function it_can_set_a_nested_object_value() {
		$object = (object) array(
			'foo' => (object) array(
				'bar' => 'baz',
			),
		);
		$this->assertEquals( 'baz', $object->foo->bar );

		$traverser = new RecursiveDataStructureTraverser( $object );
		$traverser->update( array( 'foo', 'bar' ), 'new' );

		$this->assertEquals( 'new', $object->foo->bar );
	}

	/** @test */
	function it_can_delete_a_nested_array_value() {
		$array = array(
			'foo' => array(
				'bar' => 'baz',
			),
		);
		$this->assertArrayHasKey( 'bar', $array['foo'] );

		$traverser = new RecursiveDataStructureTraverser( $array );
		$traverser->delete( array( 'foo', 'bar' ) );

		$this->assertArrayNotHasKey( 'bar', $array['foo'] );
	}

	/** @test */
	function it_can_delete_a_nested_object_value() {
		$object = (object) array(
			'foo' => (object) array(
				'bar' => 'baz',
			),
		);
		$this->assertObjectHasAttribute( 'bar', $object->foo );

		$traverser = new RecursiveDataStructureTraverser( $object );
		$traverser->delete( array( 'foo', 'bar' ) );

		$this->assertObjectNotHasAttribute( 'bar', $object->foo );
	}
}
