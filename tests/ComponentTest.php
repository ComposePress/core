<?php


use pcfreak30\ComposePress\ComponentMock;

class ComponentTest extends PHPUnit_Framework_TestCase {
	public function test_child_component_null() {
		$this->expectException( '\Exception' );
		$component = new ComponentMock();
		$this->assertNull( $component->child->parent );
		$this->assertInstanceOf( '\pcfreak30\ComposePress\ComponentAbstract', $component->child->plugin );
	}

	public function test_child_component() {
		$component = new ComponentMock();
		$component->init();
		$this->assertInstanceOf( '\pcfreak30\ComposePress\ComponentAbstract', $component->child->parent );
		$this->assertInstanceOf( '\pcfreak30\ComposePress\ComponentAbstract', $component->child->plugin );
	}

	public function test_child_component_no_getter() {
		$component = new ComponentMock();
		$component->init();
		$this->assertFalse( $component->child2 );
	}
}
