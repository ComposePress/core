<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

/** A single hook registration captured by RecordingHooks. */
final class HookRegistration
{
    /**
     * @param callable $callback the callback exactly as it was handed to Hooks
     */
    public function __construct(
        public readonly string $hook,
        /** @var callable */
        public readonly mixed $callback,
        public readonly int $priority,
        public readonly int $arguments,
    ) {
    }
}
