<?php

declare(strict_types=1);

namespace ComposePress\Core;

final class WordPressHooks implements Hooks
{
    public function action(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
        if (!function_exists('add_action')) {
            throw new \LogicException('WordPress must be loaded before registering hooks.');
        }

        add_action($hook, $callback, $priority, $arguments);
    }

    public function filter(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
        if (!function_exists('add_filter')) {
            throw new \LogicException('WordPress must be loaded before registering hooks.');
        }

        add_filter($hook, $callback, $priority, $arguments);
    }
}
