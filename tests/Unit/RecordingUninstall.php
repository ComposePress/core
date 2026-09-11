<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginUninstall;

final class RecordingUninstall implements PluginUninstall
{
    public static function uninstall(): void
    {
    }
}
