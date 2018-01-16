<?php

use ComposePress\Core\BaseObjectMock;

class BaseObjectTest extends PHPUnit_Framework_TestCase {
	public function test_getter() {
		$this->assertEquals( 'test', ( new BaseObjectMock() )->test );
	}

	public function test_setter() {
		$obj       = new BaseObjectMock();
		$obj->test = 'new test';
		$this->assertEquals( 'new test', $obj->test );
	}

	public function test_global_getter() {
		$GLOBALS['global_test'] = 'test';
		$this->assertEquals( 'test', ( new BaseObjectMock() )->global_test );
	}

	public function test_global_setter() {
		$obj                    = new BaseObjectMock();
		$GLOBALS['global_test'] = 'test';
		$obj->global_test       = 'new test';
		$this->assertEquals( 'new test', ( new BaseObjectMock() )->global_test );
	}
}