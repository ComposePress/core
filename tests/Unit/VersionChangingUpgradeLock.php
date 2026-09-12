<?php

declare(strict_types=1);

namespace ComposePress\Core\Tests\Unit;

use ComposePress\Core\PluginUpgradeLock;

final class VersionChangingUpgradeLock implements PluginUpgradeLock
{
    public function __construct(private readonly InMemoryVersionStore $store)
    {
    }

    public bool $released = false;

    public function acquire(): bool
    {
        $this->store->set('3.0.0');
        return true;
    }

    public function release(): void
    {
        $this->released = true;
    }
}
