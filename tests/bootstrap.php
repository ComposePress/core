<?php

declare(strict_types=1);

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');
}

// phpcs:disable PSR1.Classes.ClassDeclaration,Generic.NamingConventions.CamelCapsClassName
if (!class_exists('wpdb')) {
    // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
    class wpdb
    {
        public string $options = 'wp_options';

        public function __construct(mixed ...$args)
        {
            unset($args);
        }

        public function prepare(string $query, mixed ...$args): string
        {
            return $query;
        }

        public function query(string $query): int
        {
            return 1;
        }
    }
}
// phpcs:enable PSR1.Classes.ClassDeclaration,Generic.NamingConventions.CamelCapsClassName

$GLOBALS['wpdb'] = new wpdb('', '', '', '');
$GLOBALS['composepress_test_hooks'] = [];
$GLOBALS['composepress_test_lifecycle'] = [];
$GLOBALS['composepress_test_options'] = [];
$GLOBALS['composepress_test_update_option_result'] = true;
$GLOBALS['composepress_test_upgrade_locks'] = [];
$GLOBALS['composepress_test_cache_deletes'] = [];

function get_option(string $option, mixed $default = false): mixed
{
    return $GLOBALS['composepress_test_options'][$option] ?? $default;
}

function update_option(string $option, mixed $value, bool|string $autoload = true): bool
{
    if (!$GLOBALS['composepress_test_update_option_result']) {
        return false;
    }

    $GLOBALS['composepress_test_options'][$option] = $value;
    return true;
}

function wp_cache_delete(string $key, string $group = ''): bool
{
    $GLOBALS['composepress_test_cache_deletes'][] = [$key, $group];
    return true;
}

function add_option(string $option, mixed $value = '', string $deprecated = '', bool $autoload = true): bool
{
    if (array_key_exists($option, $GLOBALS['composepress_test_options'])) {
        return false;
    }

    $GLOBALS['composepress_test_options'][$option] = $value;
    return true;
}

function delete_option(string $option): bool
{
    if (!array_key_exists($option, $GLOBALS['composepress_test_options'])) {
        return false;
    }

    unset($GLOBALS['composepress_test_options'][$option]);
    return true;
}

function add_action(string $hook, callable $callback, int $priority = 10, int $arguments = 1): bool
{
    $GLOBALS['composepress_test_hooks'][] = ['action', $hook, $priority, $arguments];
    return true;
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $arguments = 1): bool
{
    $GLOBALS['composepress_test_hooks'][] = ['filter', $hook, $priority, $arguments];
    return true;
}

function remove_action(string $hook, callable $callback, int $priority = 10): bool
{
    $GLOBALS['composepress_test_hooks'][] = ['remove_action', $hook, $priority];
    return true;
}

function remove_filter(string $hook, callable $callback, int $priority = 10): bool
{
    $GLOBALS['composepress_test_hooks'][] = ['remove_filter', $hook, $priority];
    return true;
}

function register_activation_hook(string $file, callable $callback): void
{
    $GLOBALS['composepress_test_lifecycle'][] = ['activate', $file, $callback];
}

function register_deactivation_hook(string $file, callable $callback): void
{
    $GLOBALS['composepress_test_lifecycle'][] = ['deactivate', $file, $callback];
}

function register_uninstall_hook(string $file, callable $callback): void
{
    $GLOBALS['composepress_test_lifecycle'][] = ['uninstall', $file, $callback];
}
