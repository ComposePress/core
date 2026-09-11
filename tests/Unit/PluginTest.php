<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\Plugin;
use ComposePress\Core\PluginContext;
use PHPUnit\Framework\TestCase;

final class PluginTest extends TestCase
{
    public function testBootSubscribesEachSubscriberOnce(): void
    {
        $hooks = new RecordingHooks();
        $subscriber = new RecordingSubscriber();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            [$subscriber],
            null,
            $hooks,
        );

        $plugin->boot();

        self::assertTrue($plugin->isBooted());
        self::assertSame(1, $subscriber->subscriptions);
        self::assertSame(['example_hook'], $hooks->actions);
    }

    public function testBootCannotRunTwice(): void
    {
        $plugin = new Plugin(new PluginContext('/plugins/example/example.php', 'example', '1.0.0'));
        $plugin->boot();

        $this->expectException(\LogicException::class);
        $plugin->boot();
    }

    private function invalidSubscriber(): mixed
    {
        return new \stdClass();
    }

    public function testContextRequiresIdentity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new PluginContext('', 'example', '1.0.0');
    }

    public function testBootRejectsInvalidSubscribers(): void
    {
        $invalidSubscriber = $this->invalidSubscriber();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            [$invalidSubscriber],
        );

        $this->expectException(\InvalidArgumentException::class);
        $plugin->boot();
    }
}
