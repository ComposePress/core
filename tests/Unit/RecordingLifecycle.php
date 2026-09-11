<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginLifecycle;

final class RecordingLifecycle implements PluginLifecycle
{
    public function activate(bool $networkWide): void
    {
    }

    public function deactivate(bool $networkWide): void
    {
    }
}
