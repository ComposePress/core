<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\HookSubscriber;
use ComposePress\Core\Hooks;
use ComposePress\Core\Plugin;
use ComposePress\Core\PluginContext;
use ComposePress\Core\RequirementsNotMet;
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
            hooks: $hooks,
        );

        $plugin->boot();

        self::assertTrue($plugin->isBooted());
        self::assertSame(1, $subscriber->subscriptions);
        self::assertSame(['example_hook'], $hooks->actions);
    }

    public function testRequirementsAreReportedAndGateBoot(): void
    {
        $requirement = new RecordingRequirement('PHP extension', false, 'PHP extension is missing.');
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            [new RecordingSubscriber()],
            requirements: [$requirement],
        );

        $results = $plugin->checkRequirements();

        self::assertSame('PHP extension', $results[0]->name);
        self::assertFalse($results[0]->satisfied);
        self::assertSame('PHP extension is missing.', $results[0]->message);

        $this->expectException(RequirementsNotMet::class);
        $plugin->boot();
    }

    public function testSatisfiedRequirementsAllowBoot(): void
    {
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            requirements: [new RecordingRequirement('PHP', true, 'PHP requirement satisfied.')],
        );

        $plugin->boot();

        self::assertTrue($plugin->isBooted());
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

    public function testFailedBootCannotBeRetried(): void
    {
        $hooks = new RecordingHooks();
        $subscriber = new class implements HookSubscriber {
            public function subscribe(Hooks $hooks): void
            {
                $hooks->action('before_failure', static function (): void {
                });
                throw new \RuntimeException('subscription failed');
            }
        };
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '1.0.0'),
            [$subscriber],
            hooks: $hooks,
        );

        try {
            $plugin->boot();
            self::fail('Expected boot to fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame('subscription failed', $exception->getMessage());
        }

        self::assertFalse($plugin->isBooted());
        self::assertSame(['before_failure'], $hooks->actions);

        $this->expectException(\LogicException::class);
        $plugin->boot();
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
