<?php


namespace pcfreak30\WordPress\Plugin\Framework;


/**
 * Class ComponentAbstract
 *
 * @package pcfreak30\WordPress\Plugin\Framework/*
 * @property PluginAbstract    $plugin
 * @property ComponentAbstract $parent
 */
abstract class ComponentAbstract extends BaseObjectAbstract {
	/**
	 * @var PluginAbstract
	 */
	private $plugin;

	/**
	 * @var ComponentAbstract
	 */
	private $parent;

	/**
	 *
	 */
	abstract public function init();

	/**
	 *
	 */
	public function __destruct() {
		$this->plugin = null;
		$this->parent = null;
	}

	/**
	 * @return ComponentAbstract
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * @param ComponentAbstract $parent
	 */
	public function set_parent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * @return PluginAbstract
	 */
	public function get_plugin() {
		if ( null === $this->plugin ) {
			$parent = $this;
			while ( $parent->has_parent() ) {
				$parent = $parent->parent;
			}
			$this->plugin = $parent;
		}

		return $this->plugin;
	}

	/**
	 * @return bool
	 */
	public function has_parent() {
		return null !== $this->parent;
	}

}