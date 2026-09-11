<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginActivator;
use ComposePress\Core\PluginDeactivator;

final class RecordingLifecycle implements PluginActivator, PluginDeactivator
{
    public function activate(bool $networkWide): void
    {
    }

    public function deactivate(bool $networkWide): void
    {
    }
}
