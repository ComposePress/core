<?php

namespace pcfreak30\ComposePress\Abstracts;

class ComponentMock extends Component {

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
	 * @return \pcfreak30\ComposePress\ComponentChildMock
	 */
	public function get_child() {
		return $this->child;
	}
}