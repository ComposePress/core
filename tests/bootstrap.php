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
if ( ! function_exists( '__' ) ) {
	require_once ABSPATH . WPINC . '/pomo/mo.php';
	require_once ABSPATH . WPINC . '/l10n.php';
	require_once ABSPATH . WPINC . '/class-wp-locale.php';
}
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
 *
 */
function wp_set_lang_dir() {
	if ( ! defined( 'WP_LANG_DIR' ) ) {
		if ( file_exists( WP_CONTENT_DIR . '/languages' ) && @is_dir( WP_CONTENT_DIR . '/languages' ) || ! @is_dir( ABSPATH . WPINC . '/languages' ) ) {
			/**
			 * Server path of the language directory.
			 *
			 * No leading slash, no trailing slash, full path, not relative to ABSPATH
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', WP_CONTENT_DIR . '/languages' );
			if ( ! defined( 'LANGDIR' ) ) {
				// Old static relative path maintained for limited backward compatibility - won't work in some cases.
				define( 'LANGDIR', 'wp-content/languages' );
			}
		} else {
			/**
			 * Server path of the language directory.
			 *
			 * No leading slash, no trailing slash, full path, not relative to `ABSPATH`.
			 *
			 * @since 2.1.0
			 */
			define( 'WP_LANG_DIR', ABSPATH . WPINC . '/languages' );
			if ( ! defined( 'LANGDIR' ) ) {
				// Old relative path maintained for backward compatibility.
				define( 'LANGDIR', WPINC . '/languages' );
			}
		}
	}
}

/**
 * @param $thing
 *
 * @return bool
 */
function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

/**
 * @param     $tag
 * @param     $function_to_remove
 * @param int $priority
 */
function remove_filter( $tag, $function_to_remove, $priority = 10 ) {

}

wp_set_lang_dir();