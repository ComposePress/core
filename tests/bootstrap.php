<?php

declare(strict_types=1);

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');
}

$GLOBALS['composepress_test_hooks'] = [];
$GLOBALS['composepress_test_lifecycle'] = [];
$GLOBALS['composepress_test_options'] = [];

function get_option(string $option, mixed $default = false): mixed
{
    return $GLOBALS['composepress_test_options'][$option] ?? $default;
}

function update_option(string $option, mixed $value, bool|string $autoload = true): bool
{
    $GLOBALS['composepress_test_options'][$option] = $value;
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
