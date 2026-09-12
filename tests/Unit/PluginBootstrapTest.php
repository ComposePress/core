<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\HookSubscriber;
use ComposePress\Core\Hooks;
use ComposePress\Core\Plugin;
use ComposePress\Core\PluginBootstrap;
use ComposePress\Core\PluginContext;
use ComposePress\Core\RequirementsNotMet;
use PHPUnit\Framework\TestCase;

final class PluginBootstrapTest extends TestCase
{
    public function testFirstBootStoresCurrentVersion(): void
    {
        $store = new InMemoryVersionStore();
        $plugin = new Plugin(new PluginContext('/plugins/example/example.php', 'example', '2.0.0'));

        (new PluginBootstrap($plugin, $store))->run();

        self::assertTrue($plugin->isBooted());
        self::assertSame('2.0.0', $store->get());
    }

    public function testUpgradeRunsBeforeBootAndStoresVersionAfterSuccess(): void
    {
        $store = new InMemoryVersionStore('1.0.0');
        $upgrader = new RecordingUpgrade();
        $lock = new RecordingUpgradeLock();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '2.0.0'),
            upgrader: $upgrader,
        );

        (new PluginBootstrap($plugin, $store, $lock))->run();

        self::assertSame([['1.0.0', '2.0.0']], $upgrader->upgrades);
        self::assertTrue($plugin->isBooted());
        self::assertSame('2.0.0', $store->get());
    }

    public function testVersionIsStoredBeforeBootCompletes(): void
    {
        $store = new InMemoryVersionStore('1.0.0');
        $lock = new RecordingUpgradeLock();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '2.0.0'),
            subscribers: [new class implements HookSubscriber {
                public function subscribe(Hooks $hooks): void
                {
                    throw new \RuntimeException('boot failed');
                }
            }],
        );

        $this->expectException(\RuntimeException::class);
        try {
            (new PluginBootstrap($plugin, $store, $lock))->run();
        } finally {
            self::assertSame('2.0.0', $store->get());
            self::assertFalse($plugin->isBooted());
        }
    }

    public function testLockedRereadRejectsNewerInstalledVersion(): void
    {
        $store = new InMemoryVersionStore('1.0.0');
        $lock = new VersionChangingUpgradeLock($store);
        $upgrader = new RecordingUpgrade();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '2.0.0'),
            upgrader: $upgrader,
        );

        $this->expectException(\LogicException::class);
        try {
            (new PluginBootstrap($plugin, $store, $lock))->run();
        } finally {
            self::assertSame([], $upgrader->upgrades);
            self::assertSame('3.0.0', $store->get());
            self::assertTrue($lock->released);
            self::assertFalse($plugin->isBooted());
        }
    }

    public function testUpgradeDoesNotRunWhileAnotherRequestHoldsTheLock(): void
    {
        $store = new InMemoryVersionStore('1.0.0');
        $upgrader = new RecordingUpgrade();
        $lock = new RecordingUpgradeLock();
        $lock->held = true;
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '2.0.0'),
            upgrader: $upgrader,
        );

        $this->expectException(\ComposePress\Core\UpgradeInProgress::class);
        try {
            (new PluginBootstrap($plugin, $store, $lock))->run();
        } finally {
            self::assertSame([], $upgrader->upgrades);
            self::assertSame('1.0.0', $store->get());
            self::assertFalse($plugin->isBooted());
            self::assertSame(1, $lock->acquisitions);
            self::assertSame(0, $lock->releases);
        }
    }

    public function testFailedRequirementsPreventUpgradeAndVersionPersistence(): void
    {
        $store = new InMemoryVersionStore('1.0.0');
        $upgrader = new RecordingUpgrade();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '2.0.0'),
            requirements: [new RecordingRequirement('database', false, 'Database is unavailable.')],
            upgrader: $upgrader,
        );

        try {
            (new PluginBootstrap($plugin, $store))->run();
            self::fail('Expected RequirementsNotMet.');
        } catch (RequirementsNotMet) {
        }

        self::assertSame([], $upgrader->upgrades);
        self::assertSame('1.0.0', $store->get());
        self::assertFalse($plugin->isBooted());
    }

    public function testCurrentVersionSkipsUpgrade(): void
    {
        $store = new InMemoryVersionStore('2.0.0');
        $upgrader = new RecordingUpgrade();
        $plugin = new Plugin(
            new PluginContext('/plugins/example/example.php', 'example', '2.0.0'),
            upgrader: $upgrader,
        );

        (new PluginBootstrap($plugin, $store))->run();

        self::assertSame([], $upgrader->upgrades);
        self::assertTrue($plugin->isBooted());
    }

    public function testNewerInstalledVersionFailsBeforeBoot(): void
    {
        $store = new InMemoryVersionStore('3.0.0');
        $plugin = new Plugin(new PluginContext('/plugins/example/example.php', 'example', '2.0.0'));

        $this->expectException(\LogicException::class);
        (new PluginBootstrap($plugin, $store))->run();
    }
}
