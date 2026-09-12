<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginUpgrade;

final class RecordingUpgrade implements PluginUpgrade
{
    /** @var list<array{string, string}> */
    public array $upgrades = [];

    public function upgrade(string $fromVersion, string $toVersion): void
    {
        $this->upgrades[] = [$fromVersion, $toVersion];
    }
}
