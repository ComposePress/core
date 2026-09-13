<?php

declare(strict_types=1);

namespace ComposePress\Core\Testing;

use ComposePress\Core\PluginUninstall;

/**
 * PluginUninstall spy. Uninstall is a static callback contract, so this fake
 * records invocations on static state and can be passed as a class string.
 */
final class SpyingUninstall implements PluginUninstall
{
    /** @var list<string> class strings passed to uninstall(), in call order */
    public static array $invocations = [];

    public static function uninstall(): void
    {
        self::$invocations[] = static::class;
    }

    public static function reset(): void
    {
        self::$invocations = [];
    }
}
