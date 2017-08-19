<?php

namespace pcfreak30\WordPress\Plugin\Framework;

class ComponentMock extends ComponentAbstract {

	private $child;
	private $child2;

	public function __construct() {
		$this->child  = new ComponentChildMock();
		$this->child2 = new ComponentChildMock();
	}

	/**
	 *
	 */
	public function init() {
		$this->setup_components();
	}

	/**
	 * @return \ComponentChildMock
	 */
	public function get_child() {
		return $this->child;
	}
}