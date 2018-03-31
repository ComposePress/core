<?php

namespace ComposePress\Core\Traits;

use ComposePress\Core\Abstracts\Plugin;

/**
 * Trait Component
 *
 * @package ComposePress\Core\Traits
 */
trait Component {
	use BaseObject;
	/**
	 * @var Plugin
	 */
	private $plugin;

	/**
	 * @var Component
	 */
	private $parent;

	/**
	 *
	 */
	public function __destruct() {
		$this->plugin = null;
		$this->parent = null;
	}

	/**
	 * @return Component
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * @param Component $parent
	 */
	public function set_parent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * Magical utility method that will walk up the reference chain to get the master Plugin instance and cache it in $plugin
	 *
	 * @return Plugin
	 */
	public function get_plugin() {
		if ( null === $this->plugin ) {
			$parent = $this;
			while ( $parent->has_parent() ) {
				$parent = $parent->parent;
			}
			$this->plugin = $parent;
		}

		if ( $this->plugin === $this && ! ( $this instanceof Plugin ) ) {
			throw new \Exception( 'Plugin property is equal to self. Did you forget to set the parent or create a getter?' );
		}

		return $this->plugin;
	}

	/**
	 * Return if the current component has a parent or not
	 *
	 * @return bool
	 */
	public function has_parent() {
		return null !== $this->parent;
	}

	/**
	 * Lazy load components possibly conditionally
	 */
	protected function load_components() {
		// noop
	}

	/**
	 * Setup components and run init
	 */
	protected function setup_components() {
		$this->load_components();

		$components = $this->get_components();
		$this->set_component_parents( $components );
		/** @var \ComposePress\Core\Abstracts\Component[] $components */
		foreach ( $components as $component ) {
			if ( method_exists( $component, 'init' ) ) {
				$component->init();
			}
		}
	}

	/**
	 * Get all components with a getter and that uses the Component trait
	 *
	 * @return array|\ReflectionProperty[]
	 */
	protected function get_components() {
		$components = ( new \ReflectionClass( $this ) )->getProperties();
		$components = array_filter( $components, [ $this, 'is_component' ] );
		$components = array_map(
		/**
		 * @param \ReflectionProperty $component
		 *
		 * @return Component
		 */
			function ( $component ) {
				$getter = 'get_' . $component->name;

				return $this->$getter();
			}, $components );

		return $components;
	}

	/**
	 * Load any property on the current component based on its string value as the class via the container
	 *
	 * @param $component
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function load( $component, $args = [] ) {
		$args = (array) $args;

		if ( ! property_exists( $this, $component ) ) {
			return false;
		}

		$class = $this->$component;

		if ( ! is_string( $class ) ) {
			return false;
		}
		if ( ! class_exists( $class ) ) {
			throw new \Exception( sprintf( 'Can not find class "%s" for Component "%s" in parent Component "%s"', $class, $component, __CLASS__ ) );
		}
		$this->$component = $this->container->create( $class, $args );

		return true;
	}

	protected function is_loaded( $component ) {
		if ( ! property_exists( $this, $component ) ) {
			return false;
		}

		$property = $this->$component;

		if ( ! is_object( $property ) ) {
			return false;
		}

		if ( $property instanceof \stdClass ) {
			return false;
		}

		return true;
	}

	protected function is_component( $component ) {
		static $cache = [];

		if ( ! is_object( $component ) ) {
			if ( ! is_string( $component ) ) {
				return false;
			}
			$getter = 'get_' . $component;
			if ( ! ( method_exists( $this, $getter ) && ( new \ReflectionMethod( $this, $getter ) )->isPublic() ) ) {
				return false;
			}
			$component = $this->$getter();
		}

		if ( ! is_object( $component ) ) {
			return false;
		}

		if ( $component instanceof \stdClass ) {
			return false;
		}

		$hash = spl_object_hash( $component );

		if ( isset( $cache[ $hash ] ) ) {
			return $cache[ $hash ];
		}


		$trait = __TRAIT__;
		$used  = class_uses( $component );
		if ( ! isset( $used[ $trait ] ) ) {
			$parents = class_parents( $component );
			while ( ! isset( $used[ $trait ] ) && $parents ) {
				//get trait used by parents
				$used = class_uses( array_pop( $parents ) );
			}
		}

		$cache[ $hash ] = array_flip( $used )[ $trait ];

		return $cache[ $hash ];
	}

	/**
	 * Set the parent reference for the given components to the current component
	 *
	 * @param $components
	 */
	protected function set_component_parents( $components ) {
		/** @var Component $component */
		foreach ( $components as $component ) {
			$component->parent = $this;
		}
	}

	/**
	 * The super init method h magic happens
	 */
	abstract public function init();

}