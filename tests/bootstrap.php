<?php
/**
 *
 */
define( 'ABSPATH', '/tmp/wordpress/' );
define( 'WPINC', 'wp-includes' );
// Initialize composer
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! class_exists( '\PHPUnit_Framework_TestCase' ) && class_exists( '\PHPUnit\Framework\TestCase' ) ) {
	/**
	 * Class PHPUnit_Framework_TestCase
	 */
	class_alias( '\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase' );
}
WP_Mock::bootstrap();
require_once ABSPATH . 'wp-includes/class-wp-error.php';
require_once ABSPATH . 'wp-includes/load.php';
/**
 * @param $string
 *
 * @return string
 */
function trailingslashit( $string ) {
	return untrailingslashit( $string ) . '/';
}

/**
 * @param $string
 *
 * @return string
 */
function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}


/**
 * @param     $tag
 * @param     $function_to_remove
 * @param int $priority
 */
function remove_filter( $tag, $function_to_remove, $priority = 10 ) {

}

wp_load_translations_early();