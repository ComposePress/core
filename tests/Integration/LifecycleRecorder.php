<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Integration;

use ComposePress\Core\PluginLifecycle;
use ComposePress\Core\PluginUninstall;

final class LifecycleRecorder implements PluginLifecycle, PluginUninstall
{
    /** @var list<string> */
    public static array $events = [];

    public function activate(bool $networkWide): void
    {
        self::$events[] = 'activate:' . (int) $networkWide;
    }

    public function deactivate(bool $networkWide): void
    {
        self::$events[] = 'deactivate:' . (int) $networkWide;
    }

    public static function uninstall(): void
    {
        self::$events[] = 'uninstall';
    }
}
