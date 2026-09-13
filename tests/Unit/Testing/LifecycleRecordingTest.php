<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit\Testing;

use ComposePress\Core\Testing\RecordingActivator;
use ComposePress\Core\Testing\RecordingDeactivator;
use ComposePress\Core\Testing\SpyingUninstall;
use PHPUnit\Framework\TestCase;

final class LifecycleRecordingTest extends TestCase
{
    protected function setUp(): void
    {
        SpyingUninstall::reset();
    }

    public function testActivatorRecordsNetworkWideFlagPerInvocation(): void
    {
        $activator = new RecordingActivator();

        $activator->activate(true);
        $activator->activate(false);

        self::assertSame([true, false], $activator->invocations());
        self::assertSame(2, $activator->count());
    }

    public function testDeactivatorRecordsNetworkWideFlagPerInvocation(): void
    {
        $deactivator = new RecordingDeactivator();

        $deactivator->deactivate(true);

        self::assertSame([true], $deactivator->invocations());
        self::assertSame(1, $deactivator->count());
    }

    public function testUninstallSpyRecordsStaticInvocations(): void
    {
        SpyingUninstall::uninstall();
        SpyingUninstall::uninstall();

        self::assertSame([SpyingUninstall::class, SpyingUninstall::class], SpyingUninstall::$invocations);

        SpyingUninstall::reset();

        self::assertSame([], SpyingUninstall::$invocations);
    }
}
