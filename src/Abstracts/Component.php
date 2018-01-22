<?php


namespace ComposePress\Core\Abstracts;


/**
 * Class Component
 *
 * @package ComposePress\Core\Abstracts
 * @property Plugin    $plugin
 * @property Component $parent
 */
abstract class Component extends BaseObject {
	use \ComposePress\Core\Traits\Component;
}
