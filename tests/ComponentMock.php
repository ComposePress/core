<?php

namespace ComposePress\Core;

use ComposePress\Core\Abstracts\Component;

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
	 * @return \ComposePress\Core\ComponentChildMock
	 */
	public function get_child() {
		return $this->child;
	}
}