<?php

declare(strict_types=1);

namespace ComposePress\Core;

interface Hooks
{
    public function action(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void;

    public function filter(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void;

    public function removeAction(string $hook, callable $callback, int $priority = 10): bool;

    public function removeFilter(string $hook, callable $callback, int $priority = 10): bool;
}
