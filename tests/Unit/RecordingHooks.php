<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\Hooks;

final class RecordingHooks implements Hooks
{
    /** @var list<string> */
    public array $actions = [];

    public function action(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
        $this->actions[] = $hook;
    }

    public function filter(string $hook, callable $callback, int $priority = 10, int $arguments = 1): void
    {
    }
}
