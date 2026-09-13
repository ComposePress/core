<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

use ComposePress\Core\HookSubscriber;
use ComposePress\Core\Hooks;

/**
 * HookSubscriber fake that captures each subscribe() invocation and, by
 * default, registers one action so boot paths can be observed end to end.
 */
final class RecordingSubscriber implements HookSubscriber
{
    /** @var list<Hooks> */
    private array $received = [];

    /** @param list<string> $hookNames hook names registered on every subscribe() call */
    public function __construct(private readonly array $hookNames = ['example_hook'])
    {
    }

    public function subscribe(Hooks $hooks): void
    {
        $this->received[] = $hooks;

        foreach ($this->hookNames as $hookName) {
            $hooks->action($hookName, static function (): void {
            });
        }
    }

    /** Number of times subscribe() was invoked. */
    public function invocations(): int
    {
        return count($this->received);
    }

    /**
     * The Hooks instance handed to each subscribe() call, in order.
     *
     * @return list<Hooks>
     */
    public function receivedHooks(): array
    {
        return $this->received;
    }
}
