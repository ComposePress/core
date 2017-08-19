<?php


use pcfreak30\WordPress\Plugin\Framework\PluginMock;

class PluginTest extends PHPUnit_Framework_TestCase {
	public function test_get_slug() {
		$this->assertEquals( 'test-plugin', ( new PluginMock() )->get_slug() );
	}

	public function test_get_safe_slug() {
		$this->assertEquals( 'test_plugin', ( new PluginMock() )->get_safe_slug() );
	}

	public function test_get_version() {
		$this->assertEquals( '0.1.0', ( new PluginMock() )->get_version() );
	}
}
