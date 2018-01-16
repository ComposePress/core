<?php

namespace ComposePress\Core;

use ComposePress\Core\Abstracts\Plugin;

class PluginMock extends Plugin {

	const PLUGIN_SLUG = 'test-plugin';

	const VERSION = '0.1.0';

	/**
	 * @return void
	 */
	public function activate() {
		// TODO: Implement activate() method.
	}

	/**
	 * @return void
	 */
	public function deactivate() {
		// TODO: Implement deactivate() method.
	}

	/**
	 * @return void
	 */
	public function uninstall() {
		// TODO: Implement uninstall() method.
	}

	protected function set_container() {

	}

}