<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginUpgradeLock;

final class RecordingUpgradeLock implements PluginUpgradeLock
{
    public bool $held = false;

    public int $acquisitions = 0;

    public int $releases = 0;

    public function acquire(): bool
    {
        $this->acquisitions++;
        if ($this->held) {
            return false;
        }

        $this->held = true;
        return true;
    }

    public function release(): void
    {
        $this->releases++;
        $this->held = false;
    }
}
