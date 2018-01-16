<?php


use ComposePress\Core\ComponentMock;

class ComponentTest extends PHPUnit_Framework_TestCase {
	public function test_child_component_null() {
		if ( method_exists( $this, 'expectException' ) ) {
			$this->expectException( '\Exception' );
		} else {
			$this->setExpectedException( '\Exception' );
		}
		$component = new ComponentMock();
		$this->assertNull( $component->child->parent );
		$this->assertInstanceOf( '\ComposePress\Core\Abstracts\Component', $component->child->plugin );
	}

	public function test_child_component() {
		$component = new ComponentMock();
		$component->init();
		$this->assertInstanceOf( '\ComposePress\Core\Abstracts\Component', $component->child->parent );
		$this->assertInstanceOf( '\ComposePress\Core\Abstracts\Component', $component->child->plugin );
	}

	public function test_child_component_no_getter() {
		$component = new ComponentMock();
		$component->init();
		$this->assertFalse( $component->child2 );
	}
}
