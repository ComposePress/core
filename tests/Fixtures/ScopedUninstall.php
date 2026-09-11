<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Fixtures;

use ComposePress\Core\PluginUninstall;

final class ScopedUninstall implements PluginUninstall
{
    public static function uninstall(): void
    {
    }
}
