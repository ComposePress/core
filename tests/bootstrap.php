<?php

declare(strict_types=1);

// Preload the real WP_Hook engine for the Testing fakes when the vendored
// WordPress core (roots/wordpress-no-content) is present.
$wpCoreDir = getenv('WP_CORE_DIR') ?: dirname(__DIR__) . '/var/wordpress';
if (is_file($wpCoreDir . '/wp-includes/class-wp-hook.php')) {
    require_once $wpCoreDir . '/wp-includes/class-wp-hook.php';
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', '/tmp/wordpress/wp-content/plugins');
}

$GLOBALS['composepress_test_hooks'] = [];
$GLOBALS['composepress_test_lifecycle'] = [];

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
