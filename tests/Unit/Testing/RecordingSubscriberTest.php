<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit\Testing;

use ComposePress\Core\Testing\RecordingHooks;
use ComposePress\Core\Testing\RecordingSubscriber;
use PHPUnit\Framework\TestCase;

final class RecordingSubscriberTest extends TestCase
{
    public function testCapturesSubscribeInvocations(): void
    {
        $hooks = new RecordingHooks();
        $subscriber = new RecordingSubscriber(['alpha', 'beta']);

        $subscriber->subscribe($hooks);
        $subscriber->subscribe($hooks);

        self::assertSame(2, $subscriber->invocations());
        self::assertSame([$hooks, $hooks], $subscriber->receivedHooks());
        self::assertSame(['alpha', 'beta', 'alpha', 'beta'], $hooks->actionNames());
    }

    public function testRegistersDefaultExampleHook(): void
    {
        $hooks = new RecordingHooks();
        $subscriber = new RecordingSubscriber();

        $subscriber->subscribe($hooks);

        self::assertSame(['example_hook'], $hooks->actionNames());
    }
}
