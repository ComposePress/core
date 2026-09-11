<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\HookSubscriber;
use ComposePress\Core\Hooks;

final class RecordingSubscriber implements HookSubscriber
{
    public int $subscriptions = 0;

    public function subscribe(Hooks $hooks): void
    {
        $this->subscriptions++;
        $hooks->action('example_hook', static function (): void {
        });
    }
}
