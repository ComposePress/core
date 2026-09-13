<?php

/**
 * WordPress plugin API helper.
 *
 * This file contains a verbatim copy of `_wp_filter_build_unique_id()` from
 * WordPress core `wp-includes/plugin.php` (GPL-2.0-or-later, Copyright the
 * WordPress contributors, https://wordpress.org). WordPress is GPL-2.0-or-later,
 * which is compatible with this package's LGPL-3.0-or-later license; when this
 * file is combined with WordPress code, that code keeps its GPL terms.
 *
 * WP_Hook::add_filter() and ::remove_filter() call the unqualified global
 * function, so it must exist here in the global namespace for the
 * ComposePress\Core\Testing fakes to run without loading all of WordPress.
 * The guard keeps WordPress's own definition authoritative whenever
 * wp-includes/plugin.php is already loaded.
 */

declare(strict_types=1);

if (!function_exists('_wp_filter_build_unique_id')) {
    /**
     * Builds the unique callback ID used to key registrations inside a
     * priority bucket.
     *
     * String callbacks, static-callable `Class::method` arrays, and the
     * equivalent string form all reduce to the same ID, so replacing and
     * removing a callback behaves identically in both forms. Instance methods
     * and closures are identified by their object.
     *
     * @param string   $hook_name Signature parity with WP_Hook's calls but unused.
     * @param callable $callback  The function to generate ID for.
     * @param mixed    $priority  Signature parity with WP_Hook's calls but unused.
     * @return string|null The unique function ID, or null only when the
     *                     callback reduces to no readable id, matching the
     *                     upstream implementation's fall-through.
     */
    function _wp_filter_build_unique_id(string $hook_name, $callback, mixed $priority)
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_object($callback)) {
            // Closures and invokable objects normalize to [object, ''] so they
            // are keyed by the object, like instance methods.
            $callback = array($callback, '');
        } else {
            $callback = (array) $callback;
        }

        if (is_object($callback[0])) {
            return spl_object_hash($callback[0]) . $callback[1];
        } elseif (is_string($callback[0])) {
            return $callback[0] . '::' . $callback[1];
        }

        // Reached only for callbacks that cannot reduce to an id; the upstream
        // implementation falls through and returns null implicitly.
        return null;
    }
}
